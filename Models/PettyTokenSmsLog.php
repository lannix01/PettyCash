<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyTokenSmsLog extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_token_sms_logs';

    protected $fillable = [
        'payment_request_id',
        'sms_box',
        'sms_kind',
        'sender',
        'receiver',
        'sms_body',
        'parsed_reference',
        'parsed_meter_number',
        'parsed_token',
        'parsed_units',
        'parsed_amount',
        'parsed_token_amount',
        'parsed_other_charges',
        'parsed_transaction_cost',
        'parsed_payment_type',
        'sms_received_at',
        'sms_sent_at',
        'matched_confidence',
        'is_matched',
        'matched_by_user_id',
        'matched_at',
        'gateway_device_id',
    ];

    protected $casts = [
        'parsed_units' => 'float',
        'parsed_amount' => 'float',
        'parsed_token_amount' => 'float',
        'parsed_other_charges' => 'float',
        'parsed_transaction_cost' => 'float',
        'matched_confidence' => 'float',
        'is_matched' => 'boolean',
        'sms_received_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'matched_at' => 'datetime',
    ];

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PettyTokenPaymentRequest::class, 'payment_request_id');
    }

    public function gatewayDevice(): BelongsTo
    {
        return $this->belongsTo(PettyGatewayDevice::class, 'gateway_device_id');
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(PettyUser::class, 'matched_by_user_id');
    }
}
