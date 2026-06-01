<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\PettyGatewayDevice;
use App\Modules\PettyCash\Models\PettyGatewayOutbox;
use App\Modules\PettyCash\Models\PettyTokenPaymentRequest;
use App\Modules\PettyCash\Models\PettyTokenSmsLog;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Support\PettyDatabase;
use App\Modules\PettyCash\Support\TokenPaymentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TokenPaymentRequestService
{
    public function __construct(
        private readonly GatewayOutboxService $outboxService,
        private readonly SmsMatchingService $matchingService,
        private readonly SmsgateSmsService $smsgateSms,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public function createDraft(array $data, PettyUser $user): PettyTokenPaymentRequest
    {
        $hostel = !empty($data['hostel_id']) ? Hostel::query()->find($data['hostel_id']) : null;
        $paymentType = (string) $data['payment_type'];

        return PettyTokenPaymentRequest::query()->create([
            'uuid' => (string) Str::uuid(),
            'payment_type' => $paymentType,
            'paybill_number' => $this->paybillForType($paymentType),
            'meter_number' => trim((string) $data['meter_number']),
            'amount' => (float) $data['amount'],
            'receiver_phone' => $this->normalizePhone((string) ($data['receiver_phone'] ?? '')),
            'customer_name' => trim((string) ($data['customer_name'] ?? ($hostel->contact_person ?? $hostel->hostel_name ?? ''))) ?: null,
            'hostel_id' => $hostel?->id,
            'gateway_device_id' => !empty($data['gateway_device_id']) ? (int) $data['gateway_device_id'] : null,
            'status' => TokenPaymentStatus::DRAFT,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'operator_initials' => $this->initialsForUser($user),
            'requires_manual_confirmation' => true,
            'created_by' => $user->id,
        ]);
    }

    public function sendToGateway(PettyTokenPaymentRequest $request): PettyTokenPaymentRequest
    {
        if (!$request->gateway_device_id) {
            throw ValidationException::withMessages([
                'gateway_device_id' => 'Select a gateway device before sending.',
            ]);
        }

        $this->transition($request, TokenPaymentStatus::SENT_TO_PHONE);

        $request->forceFill([
            'sent_to_phone_at' => now(),
        ])->save();

        $this->outboxService->queuePaymentRequest($request);

        return $request->fresh(['hostel', 'gatewayDevice']);
    }

    public function startPaymentFromGateway(PettyTokenPaymentRequest $request): PettyTokenPaymentRequest
    {
        if ($request->status === TokenPaymentStatus::SENT_TO_PHONE) {
            $this->transition($request, TokenPaymentStatus::AWAITING_PAYMENT);
        } elseif ($request->status !== TokenPaymentStatus::AWAITING_PAYMENT) {
            throw ValidationException::withMessages([
                'request' => 'This payment request is not ready to be started from the gateway phone.',
            ]);
        }

        $request->forceFill([
            'payment_started_at' => $request->payment_started_at ?: now(),
        ])->save();

        return $request->fresh(['hostel', 'gatewayDevice']);
    }

    public function confirmMatch(PettyTokenPaymentRequest $request, PettyUser $user, ?string $smsBody = null, ?string $initials = null): PettyTokenPaymentRequest
    {
        if (!$request->matched_mpesa_sms_log_id || !$request->matched_kplc_sms_log_id) {
            throw ValidationException::withMessages([
                'request' => 'Both M-PESA and KPLC SMS must be linked before confirmation.',
            ]);
        }

        $this->transition($request, TokenPaymentStatus::CONFIRMED, [
            TokenPaymentStatus::READY_FOR_CONFIRMATION,
            TokenPaymentStatus::MATCHED,
            TokenPaymentStatus::MPESA_SMS_RECEIVED,
            TokenPaymentStatus::TOKEN_SMS_RECEIVED,
        ]);

        $finalInitials = strtoupper(trim((string) ($initials ?: $request->operator_initials ?: $this->initialsForUser($user))));
        $body = trim((string) ($smsBody ?: $this->buildOutgoingSms($request, $user, $finalInitials)));

        $request->forceFill([
            'operator_initials' => $finalInitials !== '' ? $finalInitials : null,
            'outgoing_sms_body' => $body,
            'confirmed_at' => now(),
        ])->save();

        return $request->fresh(['matchedMpesaSms', 'matchedKplcSms', 'hostel']);
    }

    public function queueCustomerSms(PettyTokenPaymentRequest $request, string $smsBody, ?string $initials = null): PettyTokenPaymentRequest
    {
        $sendGateway = $this->resolveCustomerSendGateway($request);
        if (!$sendGateway) {
            throw ValidationException::withMessages([
                'gateway_device_id' => 'A gateway device is required to send the customer SMS.',
            ]);
        }

        if (trim($request->receiver_phone ?? '') === '') {
            throw ValidationException::withMessages([
                'receiver_phone' => 'Receiver phone is required.',
            ]);
        }

        if (!in_array($request->status, [TokenPaymentStatus::CONFIRMED, TokenPaymentStatus::TOKEN_SENT], true)) {
            throw ValidationException::withMessages([
                'request' => 'Confirm the matched M-PESA and KPLC SMS before sending the customer SMS.',
            ]);
        }

        $normalizedBody = trim($smsBody);

        $smsLog = PettyTokenSmsLog::query()->create([
            'payment_request_id' => $request->id,
            'sms_box' => 'outgoing',
            'sms_kind' => 'customer',
            'receiver' => $request->receiver_phone,
            'sms_body' => $normalizedBody,
            'gateway_device_id' => $sendGateway->id,
        ]);

        $request->loadMissing(['gatewayDevice', 'outgoingCustomerSms', 'matchedMpesaSms', 'matchedKplcSms', 'hostel']);
        $request->forceFill([
            'gateway_device_id' => $sendGateway->id,
            'operator_initials' => strtoupper(trim((string) ($initials ?: $request->operator_initials))),
            'outgoing_sms_body' => $normalizedBody,
            'outgoing_customer_sms_log_id' => $smsLog->id,
        ])->save();
        $request->setRelation('gatewayDevice', $sendGateway);

        if ($sendGateway->smsgate_enabled) {
            try {
                $send = $this->smsgateSms->sendViaGatewayDevice($sendGateway, (string) $request->receiver_phone, $normalizedBody);
                $smsLog->forceFill([
                    'parsed_reference' => $send['message_id'],
                ])->save();

                $outbox = $this->outboxService->queueCustomerSms($request, $normalizedBody);
                $outbox->forceFill([
                    'payload' => array_filter([
                        'request_uuid' => $request->uuid,
                        'to' => $request->receiver_phone,
                        'body' => $normalizedBody,
                        'provider' => 'smsgate',
                        'message_id' => $send['message_id'],
                        'endpoint' => $send['endpoint'],
                        'gateway_phone' => $sendGateway->phone_number,
                    ], static fn ($value) => $value !== null && $value !== ''),
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ])->save();

                $request->notes = trim(implode("\n", array_filter([
                    trim((string) $request->notes),
                    'SMSGate queued via ' . $send['endpoint'],
                    !empty($send['message_id']) ? ('SMSGate message ID: ' . $send['message_id']) : null,
                ])));
                $request->save();

                return $request->fresh(['outgoingCustomerSms', 'gatewayDevice', 'outboxItems']);
            } catch (\Throwable $e) {
                Log::warning('SMSGate send failed; falling back to gateway outbox send.', [
                    'request_id' => $request->id,
                    'gateway_device_id' => $sendGateway->id,
                    'receiver_phone' => $request->receiver_phone,
                    'error' => $e->getMessage(),
                ]);

                $request->notes = trim(implode("\n", array_filter([
                    trim((string) $request->notes),
                    'SMSGate fallback: ' . ($e->getMessage() ?: 'Gateway send failed.'),
                    'Customer SMS queued back to gateway helper app.',
                ])));
                $request->save();
            }
        }

        $outbox = $this->outboxService->queueCustomerSms($request, $normalizedBody);
        $outbox->payment_request_id = $request->id;
        $outbox->save();

        return $request->fresh(['outgoingCustomerSms', 'gatewayDevice']);
    }

    public function activeGatewayDevice(): ?PettyGatewayDevice
    {
        return PettyGatewayDevice::query()
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->orderBy('id')
            ->first();
    }

    public function finalizeCustomerSmsDelivery(PettyTokenPaymentRequest $request, PettyGatewayOutbox $outbox): PettyTokenPaymentRequest
    {
        return PettyDatabase::transaction(function () use ($request, $outbox) {
            $request->refresh();

            $outgoingLog = $request->outgoingCustomerSms;
            if ($outgoingLog) {
                $outgoingLog->forceFill([
                    'sms_sent_at' => $outgoingLog->sms_sent_at ?: now(),
                ])->save();
            }

            if ($request->status !== TokenPaymentStatus::TOKEN_SENT) {
                $this->transition($request, TokenPaymentStatus::TOKEN_SENT, [
                    TokenPaymentStatus::CONFIRMED,
                ]);
            }

            $request->forceFill([
                'token_sent_at' => $request->token_sent_at ?: now(),
                'failure_reason' => null,
                'failed_at' => null,
            ])->save();

            $this->recordHostelGatewayPayment($request);

            return $request->fresh(['hostel', 'matchedMpesaSms', 'matchedKplcSms', 'outgoingCustomerSms']);
        });
    }

    public function cancel(PettyTokenPaymentRequest $request, ?string $reason = null): PettyTokenPaymentRequest
    {
        $this->transition($request, TokenPaymentStatus::CANCELLED, [
            TokenPaymentStatus::DRAFT,
            TokenPaymentStatus::SENT_TO_PHONE,
            TokenPaymentStatus::AWAITING_PAYMENT,
            TokenPaymentStatus::READY_FOR_CONFIRMATION,
            TokenPaymentStatus::FAILED,
        ]);

        $request->forceFill([
            'failure_reason' => $reason ? trim($reason) : $request->failure_reason,
        ])->save();

        return $request;
    }

    public function retry(PettyTokenPaymentRequest $request): PettyTokenPaymentRequest
    {
        if (!$request->gateway_device_id) {
            throw ValidationException::withMessages([
                'gateway_device_id' => 'Gateway device is required before retrying.',
            ]);
        }

        $this->transition($request, TokenPaymentStatus::SENT_TO_PHONE, [
            TokenPaymentStatus::FAILED,
        ]);

        $request->forceFill([
            'failed_at' => null,
            'failure_reason' => null,
            'sent_to_phone_at' => now(),
        ])->save();

        $this->outboxService->queuePaymentRequest($request);
        $request->forceFill(['status' => TokenPaymentStatus::AWAITING_PAYMENT])->save();

        return $request->fresh();
    }

    public function manualLink(PettyTokenPaymentRequest $request, PettyTokenSmsLog $smsLog, PettyUser $user): PettyTokenPaymentRequest
    {
        $this->matchingService->attachLogToRequest($request, $smsLog, $user->id);

        return $request->fresh(['matchedMpesaSms', 'matchedKplcSms']);
    }

    public function markFailed(PettyTokenPaymentRequest $request, ?string $reason = null): void
    {
        if (!TokenPaymentStatus::canTransition($request->status, TokenPaymentStatus::FAILED) && $request->status !== TokenPaymentStatus::FAILED) {
            return;
        }

        $request->forceFill([
            'status' => TokenPaymentStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason ? trim($reason) : $request->failure_reason,
        ])->save();
    }

    public function buildOutgoingSms(PettyTokenPaymentRequest $request, PettyUser $user, ?string $initials = null): string
    {
        $request->loadMissing(['hostel', 'matchedKplcSms']);

        $hostel = $request->hostel;
        $tokenSms = $request->matchedKplcSms;
        $coverageStart = ($tokenSms?->sms_received_at ?: $request->matched_at ?: $request->created_at ?: now());
        $coverageEnd = $this->resolveCoverageEnd($request);
        $finalInitials = strtoupper(trim((string) ($initials ?: $request->operator_initials ?: $this->initialsForUser($user))));

        $lines = [
            'SKYBRIX TOKEN PAYMENT from ' . Carbon::parse($coverageStart)->format('F jS') . ($coverageEnd ? ' to ' . Carbon::parse($coverageEnd)->format('F jS') : ''),
            'Meter: ' . $request->meter_number,
            'Token: ' . (string) ($tokenSms?->parsed_token ?: '[pending token]'),
            'Amount: KES ' . number_format((float) $request->amount, 2),
            'Units: ' . ($tokenSms?->parsed_units !== null ? rtrim(rtrim(number_format((float) $tokenSms->parsed_units, 2, '.', ''), '0'), '.') : '[pending units]'),
            '',
            'Regards,',
            'Skybrix Transmission.',
            '^' . ($finalInitials !== '' ? $finalInitials : 'XX'),
        ];

        return implode("\n", $lines);
    }

    public function paybillForType(string $paymentType): string
    {
        return $paymentType === 'postpaid' ? '888888' : '888880';
    }

    public function initialsForUser(?PettyUser $user): string
    {
        $name = trim((string) ($user?->name ?? ''));
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }

    private function resolveCoverageEnd(PettyTokenPaymentRequest $request): ?Carbon
    {
        $hostel = $request->hostel;
        if (!$hostel) {
            return null;
        }

        $start = $request->matchedKplcSms?->sms_received_at ?: $request->confirmed_at ?: $request->created_at;
        $start = $start ? Carbon::parse($start)->startOfDay() : Carbon::today();

        if (($hostel->stake ?: 'monthly') === 'semester') {
            return $start->copy()->addMonthsNoOverflow((int) config('pettycash.token_notifications.semester_months', 4));
        }

        return $start->copy()->addMonthNoOverflow();
    }

    private function recordHostelGatewayPayment(PettyTokenPaymentRequest $request): void
    {
        $request->loadMissing(['hostel', 'matchedMpesaSms', 'matchedKplcSms']);

        if (!$request->hostel_id) {
            return;
        }

        $reference = 'GW-' . $request->uuid;
        $existing = Payment::query()
            ->where('reference', $reference)
            ->where('hostel_id', $request->hostel_id)
            ->first();

        if ($existing) {
            return;
        }

        $hostel = $request->hostel;
        $paymentDate = $request->matchedKplcSms?->sms_received_at
            ?: $request->matchedMpesaSms?->sms_received_at
            ?: $request->token_sent_at
            ?: now();

        $notes = trim(implode(' | ', array_filter([
            'Gateway token workflow payment',
            'Meter ' . $request->meter_number,
            $request->matchedKplcSms?->parsed_token ? 'Token ' . $request->matchedKplcSms->parsed_token : null,
            $request->matchedMpesaSms?->parsed_reference ? 'M-PESA ' . $request->matchedMpesaSms->parsed_reference : null,
        ])));

        Payment::query()->create([
            'hostel_id' => $request->hostel_id,
            'batch_id' => null,
            'reference' => $reference,
            'amount' => (float) $request->amount,
            'transaction_cost' => (float) ($request->matchedMpesaSms?->parsed_transaction_cost ?? 0),
            'date' => Carbon::parse($paymentDate)->toDateString(),
            'receiver_name' => trim((string) ($request->customer_name ?: $hostel?->contact_person ?: $hostel?->hostel_name ?: 'Gateway Payment')) ?: 'Gateway Payment',
            'receiver_phone' => $request->receiver_phone ?: ($hostel?->phone_no ?: null),
            'notes' => $notes !== '' ? $notes : null,
            'recorded_by' => $request->created_by,
        ]);
    }

    /**
     * @param array<int,string>|null $allowedFrom
     */
    private function transition(PettyTokenPaymentRequest $request, string $to, ?array $allowedFrom = null): void
    {
        $from = (string) ($request->status ?: TokenPaymentStatus::DRAFT);

        if ($allowedFrom !== null && !in_array($from, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move request from {$from} to {$to}.",
            ]);
        }

        if ($allowedFrom === null && !TokenPaymentStatus::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move request from {$from} to {$to}.",
            ]);
        }

        $request->status = $to;

        if ($to === TokenPaymentStatus::FAILED) {
            $request->failed_at = now();
        }

        if (in_array($to, [TokenPaymentStatus::MATCHED, TokenPaymentStatus::READY_FOR_CONFIRMATION], true)) {
            $request->matched_at = now();
        }

        if ($to === TokenPaymentStatus::CONFIRMED) {
            $request->confirmed_at = now();
        }

        if ($to === TokenPaymentStatus::TOKEN_SENT) {
            $request->token_sent_at = now();
        }

        $request->save();
    }

    private function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/\s+/', '', $phone) ?: '';
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        if (preg_match('/^07\d{8}$/', $phone)) {
            $phone = '254' . substr($phone, 1);
        }

        return $phone !== '' ? $phone : null;
    }

    private function resolveCustomerSendGateway(PettyTokenPaymentRequest $request): ?PettyGatewayDevice
    {
        $active = $this->activeGatewayDevice();
        if ($active) {
            return $active;
        }

        if ($request->gateway_device_id) {
            return $request->gatewayDevice ?: PettyGatewayDevice::query()->find($request->gateway_device_id);
        }

        return null;
    }
}
