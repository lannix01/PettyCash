<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyGatewayOutbox extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_gateway_outbox';

    protected $fillable = [
        'gateway_device_id',
        'payment_request_id',
        'command_type',
        'payload',
        'status',
        'queued_at',
        'delivered_at',
        'acknowledged_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'queued_at' => 'datetime',
        'delivered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function gatewayDevice(): BelongsTo
    {
        return $this->belongsTo(PettyGatewayDevice::class, 'gateway_device_id');
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PettyTokenPaymentRequest::class, 'payment_request_id');
    }
}
