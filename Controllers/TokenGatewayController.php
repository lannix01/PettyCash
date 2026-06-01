<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\PettyGatewayDevice;
use App\Modules\PettyCash\Models\PettyTokenPaymentRequest;
use App\Modules\PettyCash\Models\PettyTokenSmsLog;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Services\SmsMatchingService;
use App\Modules\PettyCash\Services\SmsgateSmsService;
use App\Modules\PettyCash\Services\TokenPaymentRequestService;
use App\Modules\PettyCash\Support\PettyDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TokenGatewayController extends Controller
{
    public function __construct(
        private readonly TokenPaymentRequestService $requests,
        private readonly SmsMatchingService $matching,
        private readonly SmsgateSmsService $smsgate,
    ) {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $paymentType = trim((string) $request->get('payment_type', ''));
        $from = trim((string) $request->get('from', ''));
        $to = trim((string) $request->get('to', ''));
        $showRequestForm = (bool) $request->boolean('show_request_form');
        $showDeviceForm = (bool) $request->boolean('show_device_form');
        $hostelId = (int) $request->integer('hostel_id');
        $hostel = $hostelId > 0 ? Hostel::query()->find($hostelId) : null;

        $items = PettyTokenPaymentRequest::query()
            ->with(['hostel:id,hostel_name,meter_no,phone_no', 'gatewayDevice:id,name,phone_number', 'matchedMpesaSms:id,parsed_reference,payment_request_id', 'matchedKplcSms:id,parsed_token,payment_request_id'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('meter_number', 'like', '%' . $q . '%')
                        ->orWhere('receiver_phone', 'like', '%' . $q . '%')
                        ->orWhere('customer_name', 'like', '%' . $q . '%')
                        ->orWhere('uuid', 'like', '%' . $q . '%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($paymentType !== '', fn ($query) => $query->where('payment_type', $paymentType))
            ->when($from !== '', fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $devices = PettyGatewayDevice::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $activeDevice = $devices->firstWhere('is_active', true);

        $hostels = Hostel::query()
            ->orderBy('hostel_name')
            ->limit(200)
            ->get(['id', 'hostel_name', 'meter_no', 'phone_no', 'contact_person', 'amount_due']);

        $counts = [
            'open' => PettyTokenPaymentRequest::query()->whereNotIn('status', ['token_sent', 'cancelled'])->count(),
            'awaiting' => PettyTokenPaymentRequest::query()->whereIn('status', ['sent_to_phone', 'awaiting_payment', 'mpesa_sms_received', 'token_sms_received'])->count(),
            'ready' => PettyTokenPaymentRequest::query()->whereIn('status', ['ready_for_confirmation', 'confirmed'])->count(),
            'devices' => $devices->count(),
        ];

        return view('pettycash::token_gateway_requests.index', [
            'items' => $items,
            'filters' => compact('q', 'status', 'paymentType', 'from', 'to'),
            'statuses' => $this->statusOptions(),
            'devices' => $devices,
            'activeDevice' => $activeDevice,
            'hostels' => $hostels,
            'hostel' => $hostel,
            'counts' => $counts,
            'showRequestForm' => $showRequestForm,
            'showDeviceForm' => $showDeviceForm,
            'defaultGatewayPhone' => '0748538138',
            'webhookExamples' => [
                'incoming' => fn (PettyGatewayDevice $device) => route('api.petty.smsgate.webhooks.incoming', [$device->device_uuid, $device->api_token]),
                'status' => fn (PettyGatewayDevice $device) => route('api.petty.smsgate.webhooks.status', [$device->device_uuid, $device->api_token]),
            ],
        ]);
    }

    public function create(Request $request)
    {
        return redirect()->route('petty.tokens.gateway.index', [
            'show_request_form' => 1,
            'hostel_id' => $request->integer('hostel_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hostel_id' => ['nullable', 'integer', 'exists:' . $this->pettyTable('petty_hostels') . ',id'],
            'payment_type' => ['required', 'in:prepaid,postpaid'],
            'meter_number' => ['required', 'string', 'max:40'],
            'amount' => ['required', 'numeric', 'min:1'],
            'receiver_phone' => ['nullable', 'string', 'max:40'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'gateway_device_id' => ['required', 'integer', 'exists:' . $this->pettyTable('petty_gateway_devices') . ',id'],
            'notes' => ['nullable', 'string'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        /** @var PettyUser $user */
        $user = auth('petty')->user();

        $record = $this->requests->createDraft($data, $user);

        if ((bool) ($data['send_now'] ?? false)) {
            $this->requests->sendToGateway($record);

            return redirect()
                ->route('petty.tokens.gateway.show', $record)
                ->with('success', 'Gateway token request created and sent to phone.');
        }

        return redirect()
            ->route('petty.tokens.gateway.show', $record)
            ->with('success', 'Gateway token request draft created.');
    }

    public function show(PettyTokenPaymentRequest $requestRecord)
    {
        $requestRecord->load([
            'hostel',
            'gatewayDevice',
            'creator',
            'matchedMpesaSms',
            'matchedKplcSms',
            'outgoingCustomerSms',
            'outboxItems' => fn ($query) => $query->orderByDesc('id'),
            'smsLogs' => fn ($query) => $query->orderByDesc('sms_received_at')->orderByDesc('id'),
        ]);

        $candidateLogs = PettyTokenSmsLog::query()
            ->where(function ($query) use ($requestRecord) {
                $query->whereNull('payment_request_id')
                    ->orWhere('payment_request_id', $requestRecord->id);
            })
            ->where(function ($query) use ($requestRecord) {
                $query->where('parsed_meter_number', $requestRecord->meter_number)
                    ->orWhere(function ($inner) use ($requestRecord) {
                        if ($requestRecord->receiver_phone) {
                            $inner->where('receiver', $requestRecord->receiver_phone)
                                ->orWhere('sender', $requestRecord->receiver_phone);
                        }
                    });
            })
            ->orderByDesc('sms_received_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $devices = PettyGatewayDevice::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pettycash::token_gateway_requests.show', [
            'requestRecord' => $requestRecord,
            'candidateLogs' => $candidateLogs,
            'devices' => $devices,
            'generatedSms' => auth('petty')->user() ? $this->requests->buildOutgoingSms($requestRecord, auth('petty')->user()) : ($requestRecord->outgoing_sms_body ?? ''),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function send(PettyTokenPaymentRequest $requestRecord)
    {
        $this->requests->sendToGateway($requestRecord);

        return redirect()
            ->route('petty.tokens.gateway.show', $requestRecord)
            ->with('success', 'Payment request queued for the gateway phone.');
    }

    public function confirm(Request $request, PettyTokenPaymentRequest $requestRecord)
    {
        $data = $request->validate([
            'outgoing_sms_body' => ['nullable', 'string'],
            'operator_initials' => ['nullable', 'string', 'max:12'],
        ]);

        /** @var PettyUser $user */
        $user = auth('petty')->user();

        $this->requests->confirmMatch(
            $requestRecord,
            $user,
            $data['outgoing_sms_body'] ?? null,
            $data['operator_initials'] ?? null
        );

        return redirect()
            ->route('petty.tokens.gateway.show', $requestRecord)
            ->with('success', 'Match confirmed. Review the outgoing SMS before sending.');
    }

    public function queueCustomerSms(Request $request, PettyTokenPaymentRequest $requestRecord)
    {
        $data = $request->validate([
            'outgoing_sms_body' => ['required', 'string'],
            'operator_initials' => ['nullable', 'string', 'max:12'],
        ]);

        $this->requests->queueCustomerSms(
            $requestRecord,
            $data['outgoing_sms_body'],
            $data['operator_initials'] ?? null
        );

        return redirect()
            ->route('petty.tokens.gateway.show', $requestRecord)
            ->with('success', 'Customer SMS queued for delivery.');
    }

    public function manualLink(Request $request, PettyTokenPaymentRequest $requestRecord)
    {
        $data = $request->validate([
            'sms_log_id' => ['required', 'integer', 'exists:' . $this->pettyTable('petty_token_sms_logs') . ',id'],
        ]);

        /** @var PettyUser $user */
        $user = auth('petty')->user();
        $smsLog = PettyTokenSmsLog::query()->findOrFail($data['sms_log_id']);

        $this->requests->manualLink($requestRecord, $smsLog, $user);

        return redirect()
            ->route('petty.tokens.gateway.show', $requestRecord)
            ->with('success', 'SMS linked to the token payment request.');
    }

    public function refreshCheck(PettyTokenPaymentRequest $requestRecord)
    {
        $processed = 0;
        $matched = 0;

        $logs = PettyTokenSmsLog::query()
            ->whereIn('sms_kind', ['mpesa', 'kplc'])
            ->where(function ($query) use ($requestRecord) {
                $query->where('parsed_meter_number', $requestRecord->meter_number);

                if ($requestRecord->gateway_device_id) {
                    $query->orWhere(function ($inner) use ($requestRecord) {
                        $inner->where('gateway_device_id', $requestRecord->gateway_device_id)
                            ->where(function ($scoped) use ($requestRecord) {
                                $scoped->where('parsed_meter_number', $requestRecord->meter_number);
                            });
                    });
                }
            })
            ->where(function ($query) use ($requestRecord) {
                $query->whereNull('payment_request_id')
                    ->orWhere('payment_request_id', $requestRecord->id);
            })
            ->orderByDesc('sms_received_at')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        foreach ($logs as $log) {
            $processed++;

            if ((int) ($log->payment_request_id ?? 0) === (int) $requestRecord->id) {
                continue;
            }

            $result = $this->matching->processIncomingLog($log);
            if ($result && (int) $result->id === (int) $requestRecord->id) {
                $matched++;
            }
        }

        $requestRecord->refresh();

        $message = $matched > 0
            ? "Auto check complete. Matched {$matched} SMS item(s)."
            : "Auto check complete. Scanned {$processed} SMS item(s); no new safe match found.";

        return redirect()
            ->route('petty.tokens.gateway.show', $requestRecord)
            ->with('success', $message);
    }

    public function retry(PettyTokenPaymentRequest $requestRecord)
    {
        $this->requests->retry($requestRecord);

        return redirect()
            ->route('petty.tokens.gateway.show', $requestRecord)
            ->with('success', 'Gateway request retried.');
    }

    public function cancel(Request $request, PettyTokenPaymentRequest $requestRecord)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->requests->cancel($requestRecord, $data['reason'] ?? null);

        return redirect()
            ->route('petty.tokens.gateway.show', $requestRecord)
            ->with('success', 'Gateway request cancelled.');
    }

    public function storeDevice(Request $request)
    {
        $data = $request->validate($this->deviceRules());

        PettyDatabase::transaction(function () use ($data) {
            $makeActive = (bool) ($data['is_active'] ?? true);
            if ($makeActive) {
                PettyGatewayDevice::query()->update(['is_active' => false]);
            }

            PettyGatewayDevice::query()->create([
                'name' => $data['name'],
                'device_uuid' => $data['device_uuid'],
                'phone_number' => $data['phone_number'] ?? null,
                'api_token' => trim($data['api_token']),
                'is_active' => $makeActive,
                'smsgate_enabled' => (bool) ($data['smsgate_enabled'] ?? false),
                'smsgate_mode' => $data['smsgate_mode'] ?? 'auto',
                'smsgate_local_url' => trim((string) ($data['smsgate_local_url'] ?? '')) ?: null,
                'smsgate_public_url' => trim((string) ($data['smsgate_public_url'] ?? '')) ?: null,
                'smsgate_username' => trim((string) ($data['smsgate_username'] ?? '')) ?: null,
                'smsgate_password' => trim((string) ($data['smsgate_password'] ?? '')) ?: null,
                'smsgate_device_id' => trim((string) ($data['smsgate_device_id'] ?? '')) ?: null,
                'smsgate_sim_number' => !empty($data['smsgate_sim_number']) ? (int) $data['smsgate_sim_number'] : null,
            ]);
        });

        return redirect()
            ->route('petty.tokens.gateway.index')
            ->with('success', 'Gateway device saved.');
    }

    public function updateDevice(Request $request, PettyGatewayDevice $device)
    {
        $data = $request->validate($this->deviceRules($device));

        PettyDatabase::transaction(function () use ($device, $data) {
            $makeActive = (bool) ($data['is_active'] ?? false);
            if ($makeActive) {
                PettyGatewayDevice::query()->where('id', '!=', $device->id)->update(['is_active' => false]);
            }

            $device->forceFill([
                'name' => $data['name'],
                'device_uuid' => $data['device_uuid'],
                'phone_number' => $data['phone_number'] ?? null,
                'api_token' => trim($data['api_token']),
                'is_active' => $makeActive,
                'smsgate_enabled' => (bool) ($data['smsgate_enabled'] ?? false),
                'smsgate_mode' => $data['smsgate_mode'] ?? 'cloud',
                'smsgate_local_url' => trim((string) ($data['smsgate_local_url'] ?? '')) ?: null,
                'smsgate_public_url' => trim((string) ($data['smsgate_public_url'] ?? '')) ?: null,
                'smsgate_username' => trim((string) ($data['smsgate_username'] ?? '')) ?: null,
                'smsgate_password' => trim((string) ($data['smsgate_password'] ?? '')) ?: null,
                'smsgate_device_id' => trim((string) ($data['smsgate_device_id'] ?? '')) ?: null,
                'smsgate_sim_number' => !empty($data['smsgate_sim_number']) ? (int) $data['smsgate_sim_number'] : null,
            ])->save();
        });

        return redirect()
            ->route('petty.tokens.gateway.index')
            ->with('success', 'Gateway device updated.');
    }

    public function destroyDevice(PettyGatewayDevice $device)
    {
        if ($device->paymentRequests()->exists()) {
            return redirect()
                ->route('petty.tokens.gateway.index')
                ->with('error', 'This gateway device already has payment history. Keep it for audit and switch another device active instead.');
        }

        PettyDatabase::transaction(function () use ($device) {
            $wasActive = (bool) $device->is_active;
            $device->delete();

            if ($wasActive) {
                $fallback = PettyGatewayDevice::query()->orderByDesc('last_seen_at')->orderBy('id')->first();
                if ($fallback) {
                    $fallback->forceFill(['is_active' => true])->save();
                }
            }
        });

        return redirect()
            ->route('petty.tokens.gateway.index')
            ->with('success', 'Gateway device deleted.');
    }

    public function activateDevice(PettyGatewayDevice $device)
    {
        PettyDatabase::transaction(function () use ($device) {
            PettyGatewayDevice::query()->where('id', '!=', $device->id)->update(['is_active' => false]);
            $device->forceFill(['is_active' => true])->save();
        });

        return redirect()
            ->route('petty.tokens.gateway.index')
            ->with('success', 'Active gateway switched to ' . $device->name . '. New gateway requests and SMS sends will use this device.');
    }

    public function quickPayFromHostel(Request $request, Hostel $hostel)
    {
        $data = $request->validate([
            'payment_type' => ['nullable', 'in:prepaid,postpaid'],
        ]);

        $device = PettyGatewayDevice::query()
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->orderBy('id')
            ->first();

        if (!$device) {
            return redirect()
                ->route('petty.tokens.gateway.index', ['show_device_form' => 1])
                ->with('error', 'Register and activate a gateway device first.');
        }

        /** @var PettyUser $user */
        $user = auth('petty')->user();

        $record = $this->requests->createDraft([
            'hostel_id' => $hostel->id,
            'payment_type' => $data['payment_type'] ?? 'prepaid',
            'meter_number' => $hostel->meter_no,
            'amount' => (float) ($hostel->amount_due ?? 0),
            'receiver_phone' => $hostel->phone_no,
            'customer_name' => $hostel->contact_person ?: $hostel->hostel_name,
            'gateway_device_id' => $device->id,
            'notes' => 'Quick gateway request from token hostels table.',
        ], $user);

        $this->requests->sendToGateway($record);

        return redirect()
            ->route('petty.tokens.gateway.show', $record)
            ->with('success', 'Gateway payment request sent for ' . $hostel->hostel_name . '.');
    }

    public function testSms(Request $request)
    {
        $data = $request->validate([
            'gateway_device_id' => ['nullable', 'integer', 'exists:' . $this->pettyTable('petty_gateway_devices') . ',id'],
            'phone_number' => ['required', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $device = !empty($data['gateway_device_id'])
            ? PettyGatewayDevice::query()->findOrFail((int) $data['gateway_device_id'])
            : $this->requests->activeGatewayDevice();

        if (!$device) {
            return redirect()
                ->route('petty.tokens.gateway.index')
                ->with('error', 'No active gateway device is available for SMSGate testing.');
        }

        try {
            $result = $this->smsgate->sendViaGatewayDevice(
                $device,
                (string) $data['phone_number'],
                (string) $data['message']
            );

            return redirect()
                ->route('petty.tokens.gateway.index')
                ->with('success', 'Test SMS queued through ' . $result['endpoint'] . ($result['message_id'] ? ' with message ID ' . $result['message_id'] . '.' : '.'));
        } catch (\Throwable $e) {
            return redirect()
                ->route('petty.tokens.gateway.index')
                ->with('error', 'SMSGate test failed: ' . ($e->getMessage() ?: 'Unknown error.'));
        }
    }

    /**
     * @return array<string,string>
     */
    private function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'sent_to_phone' => 'Sent To Phone',
            'awaiting_payment' => 'Awaiting Payment',
            'mpesa_sms_received' => 'M-PESA SMS Received',
            'token_sms_received' => 'KPLC SMS Received',
            'matched' => 'Matched',
            'ready_for_confirmation' => 'Ready For Confirmation',
            'confirmed' => 'Confirmed',
            'token_sent' => 'Token Sent',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ];
    }

    private function pettyTable(string $table): string
    {
        return PettyDatabase::connectionName() . '.' . $table;
    }

    /**
     * @return array<string,mixed>
     */
    private function deviceRules(?PettyGatewayDevice $device = null): array
    {
        $deviceTable = $this->pettyTable('petty_gateway_devices');

        return [
            'name' => ['required', 'string', 'max:120'],
            'device_uuid' => ['required', 'string', 'max:120', Rule::unique($deviceTable, 'device_uuid')->ignore($device?->id)],
            'phone_number' => ['nullable', 'string', 'max:40'],
            'api_token' => ['required', 'string', 'max:120', Rule::unique($deviceTable, 'api_token')->ignore($device?->id)],
            'is_active' => ['nullable', 'boolean'],
            'smsgate_enabled' => ['nullable', 'boolean'],
            'smsgate_mode' => ['nullable', 'in:auto,cloud,local,public,both'],
            'smsgate_local_url' => ['nullable', 'string', 'max:255'],
            'smsgate_public_url' => ['nullable', 'string', 'max:255'],
            'smsgate_username' => ['nullable', 'string', 'max:120'],
            'smsgate_password' => ['nullable', 'string', 'max:120'],
            'smsgate_device_id' => ['nullable', 'string', 'max:191'],
            'smsgate_sim_number' => ['nullable', 'integer', 'min:1', 'max:2'],
        ];
    }
}
