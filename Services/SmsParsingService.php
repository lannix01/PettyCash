<?php

namespace App\Modules\PettyCash\Services;

use Illuminate\Support\Carbon;

class SmsParsingService
{
    /**
     * @return array<string,mixed>
     */
    public function parse(string $body, ?string $sender = null): array
    {
        $body = trim($body);
        $sender = trim((string) $sender);

        if ($body === '') {
            return ['sms_kind' => 'other'];
        }

        $mpesa = $this->parseMpesa($body, $sender);
        if ($mpesa !== null) {
            return $mpesa;
        }

        $kplc = $this->parseKplc($body, $sender);
        if ($kplc !== null) {
            return $kplc;
        }

        return ['sms_kind' => 'other'];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function parseMpesa(string $body, string $sender): ?array
    {
        if (!preg_match('/\b([A-Z0-9]{10})\b/', $body, $referenceMatch)) {
            return null;
        }

        if (!str_contains(strtoupper($body), 'CONFIRMED.')) {
            return null;
        }

        if (!preg_match('/Ksh\s*([\d,]+(?:\.\d{2})?)/i', $body, $amountMatch)) {
            return null;
        }

        $paidAt = null;
        if (preg_match('/on\s+(\d{1,2}\/\d{1,2}\/\d{2})\s+at\s+(\d{1,2}:\d{2}\s*[AP]M)/i', $body, $dateMatch)) {
            $paidAt = Carbon::createFromFormat('j/n/y g:i A', strtoupper($dateMatch[1] . ' ' . $dateMatch[2]));
        }

        $transactionCost = null;
        if (preg_match('/Transaction cost,\s*Ksh\s*([\d,]+(?:\.\d{2})?)/i', $body, $costMatch)) {
            $transactionCost = $this->toFloat($costMatch[1]);
        }

        $paymentType = str_contains(strtoupper($body), 'POSTPAID') ? 'postpaid' : 'prepaid';
        $parsedTarget = null;

        if (preg_match('/account\s+([0-9A-Za-z]+)/i', $body, $meterMatch)) {
            $parsedTarget = trim($meterMatch[1]);
        } elseif (preg_match('/sent to\s+.*?((?:\+?254|0)\d{9})/i', $body, $phoneMatch)) {
            $parsedTarget = $this->normalizePhone($phoneMatch[1]);
            $paymentType = 'send_money';
        } elseif (preg_match('/sent to\s+(.+?)\s+on\s+\d{1,2}\/\d{1,2}\/\d{2}\s+at\s+\d{1,2}:\d{2}\s*[AP]M/i', $body, $targetMatch)) {
            $parsedTarget = trim($targetMatch[1]);
            $paymentType = 'send_money';
        } else {
            return null;
        }

        return [
            'sms_kind' => 'mpesa',
            'parsed_reference' => strtoupper($referenceMatch[1]),
            'parsed_amount' => $this->toFloat($amountMatch[1]),
            'parsed_meter_number' => $parsedTarget,
            'parsed_transaction_cost' => $transactionCost,
            'parsed_payment_type' => $paymentType,
            'sms_received_at' => $paidAt,
            'sender' => $sender !== '' ? $sender : null,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function parseKplc(string $body, string $sender): ?array
    {
        if (!preg_match('/Mtr:\s*([0-9A-Za-z]+)/i', $body, $meterMatch)) {
            return null;
        }

        if (!preg_match('/Token:\s*([0-9\-]+)/i', $body, $tokenMatch)) {
            return null;
        }

        $receivedAt = null;
        if (preg_match('/Date:\s*(\d{8})\s+(\d{2}:\d{2})/i', $body, $dateMatch)) {
            $receivedAt = Carbon::createFromFormat('Ymd H:i', $dateMatch[1] . ' ' . $dateMatch[2]);
        }

        return [
            'sms_kind' => 'kplc',
            'parsed_meter_number' => trim($meterMatch[1]),
            'parsed_token' => trim($tokenMatch[1]),
            'parsed_units' => $this->matchFloat('/Units:\s*([\d.]+)/i', $body),
            'parsed_amount' => $this->matchFloat('/Amt:\s*([\d.]+)/i', $body),
            'parsed_token_amount' => $this->matchFloat('/TknAmt:\s*([\d.]+)/i', $body),
            'parsed_other_charges' => $this->matchFloat('/OtherCharges:\s*([\d.]+)/i', $body),
            'sms_received_at' => $receivedAt,
            'sender' => $sender !== '' ? $sender : null,
        ];
    }

    private function matchFloat(string $pattern, string $body): ?float
    {
        if (!preg_match($pattern, $body, $match)) {
            return null;
        }

        return $this->toFloat($match[1]);
    }

    private function toFloat(string $value): float
    {
        return (float) str_replace(',', '', trim($value));
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
        if ($phone === '') {
            return '';
        }
        if (str_starts_with($phone, '0')) {
            return '+254' . ltrim($phone, '0');
        }
        if (str_starts_with($phone, '254')) {
            return '+' . $phone;
        }
        return $phone;
    }
}
