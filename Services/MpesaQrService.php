<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Hostel;
use App\Services\SuperAdminSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MpesaQrService
{
    public function __construct(private SuperAdminSettingsService $settingsService)
    {
    }

    public function generateForHostel(Hostel $hostel, bool $refresh = false): array
    {
        $details = $this->resolveDetails($hostel);
        $payload = $details['qr_payload'];

        $path = 'pettycash/hostel_qr/hostel-' . (int) $hostel->id . '-' . now()->format('YmdHis') . '.png';
        if (!empty($hostel->qr_image_path) && Storage::disk('public')->exists((string) $hostel->qr_image_path)) {
            Storage::disk('public')->delete((string) $hostel->qr_image_path);
        }

        $image = $this->fetchQrImage($payload);
        Storage::disk('public')->put($path, $image);

        return array_merge($details, ['qr_image_path' => $path]);
    }

    private function resolveDetails(Hostel $hostel): array
    {
        $settings = $this->settingsService->platformPaymentSettings();
        $agreementType = $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none'));
        $amount = (float) ($hostel->amount_due ?? 0);
        $referenceBase = trim((string) ($hostel->meter_no ?: $hostel->phone_no ?: $hostel->id));
        $referencePrefix = trim((string) ($settings['account_reference_prefix'] ?? 'ISP')) ?: 'ISP';
        $reference = strtoupper($referencePrefix) . '-' . $this->slugReference($referenceBase);

        if ($agreementType === 'send_money') {
            $phone = trim((string) ($hostel->phone_no ?? ''));
            if ($phone === '') {
                throw new \RuntimeException('Phone number is required for a Send Money QR code.');
            }

            return [
                'qr_type' => 'Send Money',
                'qr_target' => $this->normalizePhone($phone),
                'qr_reference' => trim((string) ($hostel->contact_person ?: $hostel->hostel_name ?: $reference)),
                'qr_amount' => $amount,
                'qr_payload' => $this->buildSendMoneyPayload($phone, $hostel, $reference, $amount),
            ];
        }

        $destinationType = $settings['destination_type'] ?? 'paybill';
        if (!in_array($destinationType, ['paybill', 'till'], true)) {
            $destinationType = 'paybill';
        }

        if ($destinationType === 'till') {
            $target = trim((string) ($settings['till_number'] ?? '')) ?: '000000';
            return [
                'qr_type' => 'Buy Goods',
                'qr_target' => $target,
                'qr_reference' => $reference,
                'qr_amount' => $amount,
                'qr_payload' => $this->buildBuyGoodsPayload($target, $reference, $amount),
            ];
        }

        $target = trim((string) ($settings['paybill_number'] ?? '')) ?: '888880';
        return [
            'qr_type' => 'Paybill',
            'qr_target' => $target,
            'qr_reference' => $reference,
            'qr_amount' => $amount,
            'qr_payload' => $this->buildPaybillPayload($target, $reference, $amount),
        ];
    }

    private function buildSendMoneyPayload(string $phone, Hostel $hostel, string $reference, float $amount): string
    {
        $name = trim((string) ($hostel->contact_person ?: $hostel->hostel_name ?: 'Hostel'));
        return 'MPESA://SENDMONEY?PHONE=' . rawurlencode($this->normalizePhone($phone))
            . '&NAME=' . rawurlencode($name)
            . '&AMOUNT=' . rawurlencode(number_format($amount, 2, '.', ''))
            . '&REF=' . rawurlencode($reference);
    }

    private function buildPaybillPayload(string $paybill, string $reference, float $amount): string
    {
        return 'MPESA://PAYBILL?PAYBILL=' . rawurlencode($paybill)
            . '&ACCOUNT=' . rawurlencode($reference)
            . '&AMOUNT=' . rawurlencode(number_format($amount, 2, '.', ''));
    }

    private function buildBuyGoodsPayload(string $till, string $reference, float $amount): string
    {
        return 'MPESA://BUYGOODS?TILL=' . rawurlencode($till)
            . '&REFERENCE=' . rawurlencode($reference)
            . '&AMOUNT=' . rawurlencode(number_format($amount, 2, '.', ''));
    }

    private function fetchQrImage(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \RuntimeException('Unable to generate QR from an empty payload.');
        }

        $urls = [
            'https://quickchart.io/qr?size=320&margin=1&text=' . rawurlencode($value),
            'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode($value),
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(8)->accept('image/png')->get($url);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$response->successful()) {
                continue;
            }

            $body = $response->body();
            if ($body === '') {
                continue;
            }

            return $body;
        }

        throw new \RuntimeException('Unable to fetch a QR image from the external QR service.');
    }

    private function normalizeAgreementType(string $agreementType): string
    {
        $agreementType = strtolower(trim($agreementType));
        return in_array($agreementType, ['token', 'send_money', 'package', 'none'], true)
            ? $agreementType
            : 'none';
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '+254' . ltrim($phone, '0');
        }
        return $phone;
    }

    private function slugReference(string $value): string
    {
        return Str::upper(preg_replace('/[^A-Z0-9]/', '', trim((string) $value))) ?: 'HOSTEL' . now()->format('YmdHis');
    }
}
