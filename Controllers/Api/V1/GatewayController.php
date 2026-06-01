<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyGatewayOutbox;
use App\Modules\PettyCash\Models\PettyTokenPaymentRequest;
use App\Modules\PettyCash\Models\PettyTokenSmsLog;
use App\Modules\PettyCash\Services\GatewayDeviceService;
use App\Modules\PettyCash\Services\GatewayOutboxService;
use App\Modules\PettyCash\Services\SmsMatchingService;
use App\Modules\PettyCash\Services\SmsParsingService;
use App\Modules\PettyCash\Services\TokenPaymentRequestService;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly GatewayDeviceService $devices,
        private readonly GatewayOutboxService $outbox,
        private readonly SmsParsingService $parser,
        private readonly SmsMatchingService $matcher,
        private readonly TokenPaymentRequestService $requests,
    ) {
    }

    public function heartbeat(Request $request)
    {
        $device = $this->devices->resolveFromRequest($request);
        if (!$device) {
            return $this->errorResponse('Invalid gateway credentials.', 401);
        }

        $this->devices->touchSeen($device, (string) $request->input('app_version', ''));

        return $this->successResponse([
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'uuid' => $device->device_uuid,
                'phone_number' => $device->phone_number,
            ],
        ], 'Gateway heartbeat accepted.');
    }

    public function poll(Request $request)
    {
        $device = $this->devices->resolveFromRequest($request);
        if (!$device) {
            return $this->errorResponse('Invalid gateway credentials.', 401);
        }

        $this->devices->touchSeen($device, (string) $request->header('X-Gateway-App-Version', ''));

        $items = $this->outbox->pendingForDevice($device, (int) $request->integer('limit', 20));

        foreach ($items as $item) {
            $this->outbox->markDelivered($item);
        }

        return $this->successResponse([
            'commands' => $items->map(fn (PettyGatewayOutbox $item) => [
                'id' => $item->id,
                'command_type' => $item->command_type,
                'payload' => $item->payload,
                'queued_at' => optional($item->queued_at)->toIso8601String(),
            ])->values()->all(),
        ], 'Pending gateway commands fetched.');
    }

    public function acknowledge(Request $request, int $outboxId)
    {
        $device = $this->devices->resolveFromRequest($request);
        if (!$device) {
            return $this->errorResponse('Invalid gateway credentials.', 401);
        }

        $outbox = PettyGatewayOutbox::query()
            ->where('gateway_device_id', $device->id)
            ->findOrFail($outboxId);

        $this->outbox->markAcknowledged($outbox);

        return $this->successResponse([], 'Gateway command acknowledged.');
    }

    public function startPayment(Request $request, int $outboxId)
    {
        $device = $this->devices->resolveFromRequest($request);
        if (!$device) {
            return $this->errorResponse('Invalid gateway credentials.', 401);
        }

        $outbox = PettyGatewayOutbox::query()
            ->where('gateway_device_id', $device->id)
            ->findOrFail($outboxId);

        if ($outbox->command_type !== 'payment_request' || !$outbox->payment_request_id) {
            return $this->errorResponse('Only payment_request commands can be started.', 422);
        }

        $paymentRequest = PettyTokenPaymentRequest::query()->findOrFail($outbox->payment_request_id);
        $this->requests->startPaymentFromGateway($paymentRequest);
        $this->outbox->markAcknowledged($outbox);

        return $this->successResponse([
            'request_id' => $paymentRequest->id,
            'request_uuid' => $paymentRequest->uuid,
            'status' => $paymentRequest->status,
            'payment_started_at' => optional($paymentRequest->payment_started_at)->toIso8601String(),
        ], 'Gateway payment started.');
    }

    public function uploadIncomingSms(Request $request)
    {
        $device = $this->devices->resolveFromRequest($request);
        if (!$device) {
            return $this->errorResponse('Invalid gateway credentials.', 401);
        }

        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.sender' => ['nullable', 'string', 'max:80'],
            'messages.*.receiver' => ['nullable', 'string', 'max:80'],
            'messages.*.body' => ['required', 'string'],
            'messages.*.received_at' => ['nullable', 'date'],
        ]);

        $stored = [];
        foreach ((array) $data['messages'] as $payload) {
            $parsed = $this->parser->parse((string) $payload['body'], (string) ($payload['sender'] ?? ''));

            $receivedAt = $payload['received_at'] ?? ($parsed['sms_received_at'] ?? null);
            $log = PettyTokenSmsLog::query()
                ->where('gateway_device_id', $device->id)
                ->where('sms_box', 'incoming')
                ->where('sms_body', (string) $payload['body'])
                ->where('sender', $payload['sender'] ?? ($parsed['sender'] ?? null))
                ->where('sms_received_at', $receivedAt)
                ->first();

            if (!$log) {
                $log = PettyTokenSmsLog::query()->create([
                    'sms_box' => 'incoming',
                    'sms_kind' => (string) ($parsed['sms_kind'] ?? 'other'),
                    'sender' => $payload['sender'] ?? ($parsed['sender'] ?? null),
                    'receiver' => $payload['receiver'] ?? null,
                    'sms_body' => (string) $payload['body'],
                    'parsed_reference' => $parsed['parsed_reference'] ?? null,
                    'parsed_meter_number' => $parsed['parsed_meter_number'] ?? null,
                    'parsed_token' => $parsed['parsed_token'] ?? null,
                    'parsed_units' => $parsed['parsed_units'] ?? null,
                    'parsed_amount' => $parsed['parsed_amount'] ?? null,
                    'parsed_token_amount' => $parsed['parsed_token_amount'] ?? null,
                    'parsed_other_charges' => $parsed['parsed_other_charges'] ?? null,
                    'parsed_transaction_cost' => $parsed['parsed_transaction_cost'] ?? null,
                    'parsed_payment_type' => $parsed['parsed_payment_type'] ?? null,
                    'sms_received_at' => $receivedAt,
                    'gateway_device_id' => $device->id,
                ]);
            }

            $matchedRequest = $this->matcher->processIncomingLog($log);
            $stored[] = [
                'id' => $log->id,
                'sms_kind' => $log->sms_kind,
                'meter_number' => $log->parsed_meter_number,
                'matched_request_id' => $matchedRequest?->id,
                'matched_request_uuid' => $matchedRequest?->uuid,
            ];
        }

        return $this->successResponse([
            'logs' => $stored,
        ], 'Incoming SMS uploaded.');
    }

    public function sendResult(Request $request, int $outboxId)
    {
        $device = $this->devices->resolveFromRequest($request);
        if (!$device) {
            return $this->errorResponse('Invalid gateway credentials.', 401);
        }

        $data = $request->validate([
            'status' => ['required', 'in:sent,failed'],
            'failure_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $outbox = PettyGatewayOutbox::query()
            ->where('gateway_device_id', $device->id)
            ->findOrFail($outboxId);

        if ($data['status'] === 'sent') {
            $this->outbox->markAcknowledged($outbox);
            if ($outbox->command_type === 'send_customer_sms' && $outbox->payment_request_id) {
                $paymentRequest = PettyTokenPaymentRequest::query()
                    ->with('outgoingCustomerSms')
                    ->find($outbox->payment_request_id);

                if ($paymentRequest) {
                    $this->requests->finalizeCustomerSmsDelivery($paymentRequest, $outbox);
                }
            }
        } else {
            $this->outbox->markFailed($outbox, $data['failure_reason'] ?? null);
            if ($outbox->payment_request_id) {
                $paymentRequest = PettyTokenPaymentRequest::query()->find($outbox->payment_request_id);
                if ($paymentRequest) {
                    $this->requests->markFailed($paymentRequest, $data['failure_reason'] ?? null);
                }
            }
        }

        return $this->successResponse([], 'Gateway send result received.');
    }
}
