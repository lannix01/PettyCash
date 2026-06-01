<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\PettyGatewayDevice;
use App\Modules\PettyCash\Models\PettyGatewayOutbox;
use App\Modules\PettyCash\Models\PettyTokenPaymentRequest;

class GatewayOutboxService
{
    private const RETRY_AFTER_SECONDS = 30;

    public function queuePaymentRequest(PettyTokenPaymentRequest $request): PettyGatewayOutbox
    {
        return PettyGatewayOutbox::query()->create([
            'gateway_device_id' => $request->gateway_device_id,
            'payment_request_id' => $request->id,
            'command_type' => 'payment_request',
            'payload' => [
                'request_uuid' => $request->uuid,
                'payment_type' => $request->payment_type,
                'paybill_number' => $request->paybill_number,
                'meter_number' => $request->meter_number,
                'amount' => number_format((float) $request->amount, 2, '.', ''),
                'receiver_phone' => $request->receiver_phone,
                'customer_name' => $request->customer_name,
                'notes' => $request->notes,
            ],
            'status' => 'pending',
            'queued_at' => now(),
        ]);
    }

    public function queueCustomerSms(PettyTokenPaymentRequest $request, string $smsBody): PettyGatewayOutbox
    {
        return PettyGatewayOutbox::query()->create([
            'gateway_device_id' => $request->gateway_device_id,
            'payment_request_id' => $request->id,
            'command_type' => 'send_customer_sms',
            'payload' => [
                'request_uuid' => $request->uuid,
                'to' => $request->receiver_phone,
                'body' => $smsBody,
                'gateway_phone' => $request->gatewayDevice?->phone_number,
            ],
            'status' => 'pending',
            'queued_at' => now(),
        ]);
    }

    public function queueSmsSync(PettyGatewayDevice $device, ?Hostel $hostel = null): PettyGatewayOutbox
    {
        return PettyGatewayOutbox::query()->create([
            'gateway_device_id' => $device->id,
            'payment_request_id' => null,
            'command_type' => 'sync_sms',
            'payload' => [
                'hostel_id' => $hostel?->id,
                'hostel_name' => $hostel?->hostel_name,
                'meter_number' => $hostel?->meter_no,
                'requested_at' => now()->toIso8601String(),
            ],
            'status' => 'pending',
            'queued_at' => now(),
        ]);
    }

    public function pendingForDevice(PettyGatewayDevice $device, int $limit = 20)
    {
        $retryAfter = now()->subSeconds(self::RETRY_AFTER_SECONDS);

        return PettyGatewayOutbox::query()
            ->where('gateway_device_id', $device->id)
            ->where(function ($query) use ($retryAfter) {
                $query->where('status', 'pending')
                    ->orWhere(function ($retryQuery) use ($retryAfter) {
                        $retryQuery->where('status', 'delivered')
                            ->whereNull('acknowledged_at')
                            ->whereNull('failed_at')
                            ->whereNotNull('delivered_at')
                            ->where('delivered_at', '<=', $retryAfter);
                    });
            })
            ->orderBy('queued_at')
            ->orderBy('id')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }

    public function markDelivered(PettyGatewayOutbox $outbox): void
    {
        if (!in_array($outbox->status, ['pending', 'delivered'], true)) {
            return;
        }

        $outbox->forceFill([
            'status' => 'delivered',
            'delivered_at' => $outbox->delivered_at ?: now(),
        ])->save();
    }

    public function markAcknowledged(PettyGatewayOutbox $outbox): void
    {
        $outbox->forceFill([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ])->save();
    }

    public function markFailed(PettyGatewayOutbox $outbox, ?string $reason = null): void
    {
        $outbox->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ])->save();
    }
}
