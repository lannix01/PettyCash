<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyGatewayDevice extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_gateway_devices';

    protected $fillable = [
        'name',
        'device_uuid',
        'phone_number',
        'api_token',
        'is_active',
        'smsgate_enabled',
        'smsgate_mode',
        'smsgate_local_url',
        'smsgate_public_url',
        'smsgate_username',
        'smsgate_password',
        'smsgate_device_id',
        'smsgate_sim_number',
        'smsgate_last_tested_at',
        'smsgate_last_error',
        'app_version',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'smsgate_enabled' => 'boolean',
        'smsgate_sim_number' => 'integer',
        'smsgate_last_tested_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PettyTokenPaymentRequest::class, 'gateway_device_id');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(PettyTokenSmsLog::class, 'gateway_device_id');
    }

    public function outboxItems(): HasMany
    {
        return $this->hasMany(PettyGatewayOutbox::class, 'gateway_device_id');
    }
}
