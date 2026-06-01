<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyTokenPaymentRequest extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_token_payment_requests';

    protected $fillable = [
        'uuid',
        'payment_type',
        'paybill_number',
        'meter_number',
        'amount',
        'receiver_phone',
        'customer_name',
        'hostel_id',
        'gateway_device_id',
        'status',
        'notes',
        'outgoing_sms_body',
        'operator_initials',
        'requires_manual_confirmation',
        'matched_mpesa_sms_log_id',
        'matched_kplc_sms_log_id',
        'outgoing_customer_sms_log_id',
        'sent_to_phone_at',
        'payment_started_at',
        'mpesa_received_at',
        'token_received_at',
        'matched_at',
        'confirmed_at',
        'token_sent_at',
        'failed_at',
        'failure_reason',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'requires_manual_confirmation' => 'boolean',
        'sent_to_phone_at' => 'datetime',
        'payment_started_at' => 'datetime',
        'mpesa_received_at' => 'datetime',
        'token_received_at' => 'datetime',
        'matched_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'token_sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    public function gatewayDevice(): BelongsTo
    {
        return $this->belongsTo(PettyGatewayDevice::class, 'gateway_device_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PettyUser::class, 'created_by');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(PettyTokenSmsLog::class, 'payment_request_id');
    }

    public function outboxItems(): HasMany
    {
        return $this->hasMany(PettyGatewayOutbox::class, 'payment_request_id');
    }

    public function matchedMpesaSms(): BelongsTo
    {
        return $this->belongsTo(PettyTokenSmsLog::class, 'matched_mpesa_sms_log_id');
    }

    public function matchedKplcSms(): BelongsTo
    {
        return $this->belongsTo(PettyTokenSmsLog::class, 'matched_kplc_sms_log_id');
    }

    public function outgoingCustomerSms(): BelongsTo
    {
        return $this->belongsTo(PettyTokenSmsLog::class, 'outgoing_customer_sms_log_id');
    }
}
