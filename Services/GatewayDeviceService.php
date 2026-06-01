<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\PettyGatewayDevice;
use Illuminate\Http\Request;

class GatewayDeviceService
{
    public function resolveFromRequest(Request $request): ?PettyGatewayDevice
    {
        $token = trim((string) $request->header('X-Gateway-Token', ''));
        $uuid = trim((string) $request->header('X-Gateway-Device-UUID', ''));

        if ($token === '' || $uuid === '') {
            return null;
        }

        return PettyGatewayDevice::query()
            ->where('device_uuid', $uuid)
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    public function touchSeen(PettyGatewayDevice $device, ?string $appVersion = null): void
    {
        $device->forceFill([
            'last_seen_at' => now(),
            'app_version' => $appVersion !== null && trim($appVersion) !== '' ? trim($appVersion) : $device->app_version,
        ])->save();
    }
}
