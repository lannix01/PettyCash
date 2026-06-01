<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;

class PettySmsTemplate extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_sms_templates';

    protected $fillable = [
        'name',
        'body',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
