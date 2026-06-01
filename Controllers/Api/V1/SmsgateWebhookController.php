<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyGatewayDevice;
use App\Modules\PettyCash\Models\PettyGatewayOutbox;
use App\Modules\PettyCash\Models\PettyTokenPaymentRequest;
use App\Modules\PettyCash\Models\PettyTokenSmsLog;
use App\Modules\PettyCash\Services\GatewayOutboxService;
use App\Modules\PettyCash\Services\SmsMatchingService;
use App\Modules\PettyCash\Services\SmsParsingService;
use App\Modules\PettyCash\Services\TokenPaymentRequestService;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SmsgateWebhookController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly SmsParsingService $parser,
        private readonly SmsMatchingService $matcher,
        private readonly TokenPaymentRequestService $requests,
        private readonly GatewayOutboxService $outbox,
    ) {
    }

    public function incoming(Request $request, string $deviceUuid, string $token)
    {
        $device = $this->resolveDevice($deviceUuid, $token);
        if (!$device) {
            return $this->errorResponse('Invalid SMSGate webhook credentials.', 401);
        }

        $payload = $this->payload($request);
        $body = trim((string) ($payload['message'] ?? $payload['body'] ?? $payload['text'] ?? $payload['content'] ?? ''));
        if ($body === '') {
            return $this->successResponse(['duplicate' => false, 'stored' => false], 'Webhook received with no SMS body.');
        }

        $sender = $this->nullableString($payload['sender'] ?? $payload['from'] ?? $payload['address'] ?? null);
        $receiver = $this->nullableString($payload['recipient'] ?? $payload['to'] ?? $payload['phoneNumber'] ?? null);
        $receivedAt = $this->parseDate(
            $payload['receivedAt']
                ?? $payload['createdAt']
                ?? $payload['date']
                ?? $payload['timestamp']
                ?? null
        );

        $existing = PettyTokenSmsLog::query()
            ->where('gateway_device_id', $device->id)
            ->where('sms_box', 'incoming')
            ->where('sms_body', $body)
            ->when($sender !== null, fn ($query) => $query->where('sender', $sender))
            ->when($receivedAt !== null, fn ($query) => $query->where('sms_received_at', $receivedAt))
            ->latest('id')
            ->first();

        if ($existing) {
            return $this->successResponse([
                'duplicate' => true,
                'stored' => false,
                'log_id' => $existing->id,
            ], 'Duplicate SMSGate inbound webhook ignored.');
        }

        $parsed = $this->parser->parse($body, $sender);

        $log = PettyTokenSmsLog::query()->create([
            'sms_box' => 'incoming',
            'sms_kind' => (string) ($parsed['sms_kind'] ?? 'other'),
            'sender' => $sender ?? ($parsed['sender'] ?? null),
            'receiver' => $receiver,
            'sms_body' => $body,
            'parsed_reference' => $parsed['parsed_reference'] ?? null,
            'parsed_meter_number' => $parsed['parsed_meter_number'] ?? null,
            'parsed_token' => $parsed['parsed_token'] ?? null,
            'parsed_units' => $parsed['parsed_units'] ?? null,
            'parsed_amount' => $parsed['parsed_amount'] ?? null,
            'parsed_token_amount' => $parsed['parsed_token_amount'] ?? null,
            'parsed_other_charges' => $parsed['parsed_other_charges'] ?? null,
            'parsed_transaction_cost' => $parsed['parsed_transaction_cost'] ?? null,
            'parsed_payment_type' => $parsed['parsed_payment_type'] ?? null,
            'sms_received_at' => $receivedAt ?? ($parsed['sms_received_at'] ?? null) ?? now(),
            'gateway_device_id' => $device->id,
        ]);

        $matchedRequest = $this->matcher->processIncomingLog($log);

        return $this->successResponse([
            'duplicate' => false,
            'stored' => true,
            'log_id' => $log->id,
            'matched_request_id' => $matchedRequest?->id,
            'matched_request_uuid' => $matchedRequest?->uuid,
        ], 'SMSGate incoming SMS processed.');
    }

    public function status(Request $request, string $deviceUuid, string $token)
    {
        $device = $this->resolveDevice($deviceUuid, $token);
        if (!$device) {
            return $this->errorResponse('Invalid SMSGate webhook credentials.', 401);
        }

        $event = strtolower(trim((string) $request->input('event', '')));
        $payload = $this->payload($request);
        $messageId = $this->nullableString($payload['messageId'] ?? $payload['id'] ?? null);
        $recipient = $this->nullableString($payload['recipient'] ?? $payload['phoneNumber'] ?? $payload['to'] ?? null);
        $failureReason = $this->nullableString($payload['reason'] ?? $payload['error'] ?? $payload['failureReason'] ?? null);

        $outbox = $this->findCustomerSmsOutbox($device, $messageId, $recipient);
        $smsLog = $this->findCustomerSmsLog($device, $messageId, $recipient);
        $paymentRequest = $smsLog?->paymentRequest ?: $outbox?->paymentRequest;

        if ($event === 'sms:failed') {
            if ($outbox) {
                $this->outbox->markFailed($outbox, $failureReason ?: 'SMSGate reported send failure.');
            }

            if ($paymentRequest) {
                $this->requests->markFailed($paymentRequest, $failureReason ?: 'SMSGate reported send failure.');
            }

            return $this->successResponse([
                'message_id' => $messageId,
                'status' => 'failed',
            ], 'SMSGate failed status processed.');
        }

        if (!in_array($event, ['sms:sent', 'sms:delivered'], true)) {
            return $this->successResponse([
                'message_id' => $messageId,
                'status' => 'ignored',
            ], 'SMSGate webhook event ignored.');
        }

        if ($smsLog && !$smsLog->sms_sent_at) {
            $smsLog->forceFill([
                'sms_sent_at' => $this->parseDate(
                    $payload['sentAt'] ?? $payload['deliveredAt'] ?? $payload['createdAt'] ?? null
                ) ?? now(),
            ])->save();
        }

        if ($outbox) {
            $this->outbox->markAcknowledged($outbox);
        }

        if ($paymentRequest && $paymentRequest->status !== 'token_sent') {
            $this->requests->finalizeCustomerSmsDelivery(
                $paymentRequest,
                $outbox ?: new PettyGatewayOutbox([
                    'gateway_device_id' => $device->id,
                    'payment_request_id' => $paymentRequest->id,
                    'command_type' => 'send_customer_sms',
                    'status' => 'acknowledged',
                    'acknowledged_at' => now(),
                ]),
            );
        }

        return $this->successResponse([
            'message_id' => $messageId,
            'status' => $event === 'sms:delivered' ? 'delivered' : 'sent',
            'request_id' => $paymentRequest?->id,
        ], 'SMSGate status webhook processed.');
    }

    private function resolveDevice(string $deviceUuid, string $token): ?PettyGatewayDevice
    {
        $device = PettyGatewayDevice::query()
            ->where('device_uuid', trim($deviceUuid))
            ->first();

        if (!$device) {
            return null;
        }

        return hash_equals((string) $device->api_token, (string) $token) ? $device : null;
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(Request $request): array
    {
        $payload = $request->input('payload', []);

        return is_array($payload) ? $payload : [];
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string !== '' ? $string : null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        try {
            return Carbon::parse($string);
        } catch (\Throwable) {
            return null;
        }
    }

    private function findCustomerSmsOutbox(PettyGatewayDevice $device, ?string $messageId, ?string $recipient): ?PettyGatewayOutbox
    {
        return PettyGatewayOutbox::query()
            ->where('gateway_device_id', $device->id)
            ->where('command_type', 'send_customer_sms')
            ->orderByDesc('id')
            ->get()
            ->first(function (PettyGatewayOutbox $outbox) use ($messageId, $recipient) {
                $payloadMessageId = trim((string) data_get($outbox->payload, 'message_id'));
                $payloadRecipient = trim((string) data_get($outbox->payload, 'to'));

                if ($messageId !== null && $payloadMessageId !== '' && $payloadMessageId === $messageId) {
                    return true;
                }

                return $messageId === null
                    && $recipient !== null
                    && $payloadRecipient !== ''
                    && $payloadRecipient === $recipient;
            });
    }

    private function findCustomerSmsLog(PettyGatewayDevice $device, ?string $messageId, ?string $recipient): ?PettyTokenSmsLog
    {
        $query = PettyTokenSmsLog::query()
            ->where('gateway_device_id', $device->id)
            ->where('sms_box', 'outgoing')
            ->where('sms_kind', 'customer')
            ->latest('id');

        if ($messageId !== null) {
            $match = (clone $query)->where('parsed_reference', $messageId)->first();
            if ($match) {
                return $match;
            }
        }

        if ($recipient !== null) {
            return $query->where('receiver', $recipient)->first();
        }

        return $query->first();
    }
}
