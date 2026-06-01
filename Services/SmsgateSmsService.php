<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\PettyGatewayDevice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SmsgateSmsService
{
    private const CLOUD_BASE_URL = 'https://api.sms-gate.app';

    /**
     * @return array{message_id:string|null,endpoint:string,response:array<string,mixed>}
     */
    public function sendViaGatewayDevice(PettyGatewayDevice $device, string $to, string $body): array
    {
        if (!$device->smsgate_enabled) {
            throw ValidationException::withMessages([
                'gateway_device_id' => 'SMSGate is not enabled on this gateway device.',
            ]);
        }

        $username = trim((string) $device->smsgate_username);
        $password = trim((string) $device->smsgate_password);
        $deviceId = trim((string) $device->smsgate_device_id);
        $phone = $this->normalizePhone($to);
        $message = trim($body);

        if ($username === '' || $password === '' || $deviceId === '') {
            throw ValidationException::withMessages([
                'gateway_device_id' => 'SMSGate credentials are incomplete for this gateway device.',
            ]);
        }

        if ($phone === '' || $message === '') {
            throw ValidationException::withMessages([
                'outgoing_sms_body' => 'Destination phone number and SMS body are required.',
            ]);
        }

        $payload = [
            'textMessage' => [
                'text' => $message,
            ],
            'phoneNumbers' => [$phone],
            'deviceId' => $deviceId,
            'priority' => 1,
            'ttl' => 300,
            'withDeliveryReport' => true,
        ];

        if (!empty($device->smsgate_sim_number)) {
            $payload['simNumber'] = (int) $device->smsgate_sim_number;
        }

        $attempts = $this->buildEndpointAttempts($device);
        if ($attempts === []) {
            throw ValidationException::withMessages([
                'gateway_device_id' => 'No SMSGate local/public endpoint is configured on this gateway device.',
            ]);
        }

        $lastError = 'SMSGate send failed.';
        foreach ($attempts as $baseUrl) {
            try {
                $response = Http::withBasicAuth($username, $password)
                    ->acceptJson()
                    ->asJson()
                    ->timeout(15)
                    ->post(rtrim($baseUrl, '/') . '/3rdparty/v1/messages', $payload);

                $json = $response->json();
                if ($response->successful()) {
                    $device->forceFill([
                        'smsgate_last_tested_at' => now(),
                        'smsgate_last_error' => null,
                    ])->save();

                    return [
                        'message_id' => $this->extractMessageId($json),
                        'endpoint' => $baseUrl,
                        'response' => is_array($json) ? $json : [],
                    ];
                }

                $lastError = $this->extractErrorMessage($json, $response->body(), $response->status());
            } catch (\Throwable $e) {
                $lastError = $e->getMessage() ?: 'SMSGate send failed.';
            }
        }

        $device->forceFill([
            'smsgate_last_error' => $lastError,
        ])->save();

        throw ValidationException::withMessages([
            'outgoing_sms_body' => $lastError,
        ]);
    }

    /**
     * @return array<int,string>
     */
    private function buildEndpointAttempts(PettyGatewayDevice $device): array
    {
        $local = $this->normalizeBaseUrl((string) $device->smsgate_local_url);
        $public = $this->normalizeBaseUrl((string) $device->smsgate_public_url);
        $cloud = self::CLOUD_BASE_URL;
        $mode = strtolower(trim((string) ($device->smsgate_mode ?: 'auto')));

        $ordered = match ($mode) {
            'local' => [$local],
            'public' => [$public],
            'cloud' => [$cloud],
            'both' => [$local, $public],
            default => [$cloud, $public, $local],
        };

        return array_values(array_filter(array_unique($ordered)));
    }

    private function normalizeBaseUrl(string $value): string
    {
        $url = trim($value);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'http://' . $url;
        }

        return rtrim($url, '/');
    }

    private function normalizePhone(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        $keepPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?: '';
        if ($digits === '') {
            return '';
        }

        return $keepPlus ? ('+' . $digits) : $digits;
    }

    /**
     * @param mixed $json
     */
    private function extractMessageId($json): ?string
    {
        if (!is_array($json)) {
            return null;
        }

        return Arr::get($json, 'id')
            ?? Arr::get($json, 'data.id')
            ?? Arr::get($json, 'messageId')
            ?? Arr::get($json, 'data.messageId');
    }

    /**
     * @param mixed $json
     */
    private function extractErrorMessage($json, string $rawBody, int $status): string
    {
        if (is_array($json)) {
            $message = Arr::get($json, 'message')
                ?? Arr::get($json, 'error.message')
                ?? Arr::get($json, 'details')
                ?? Arr::get($json, 'error');

            if (is_string($message) && trim($message) !== '') {
                return "SMSGate error ({$status}): " . trim($message);
            }
        }

        $raw = trim($rawBody);
        return $raw !== '' ? "SMSGate error ({$status}): {$raw}" : "SMSGate error ({$status}).";
    }
}
