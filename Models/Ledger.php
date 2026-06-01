<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use UsesPettyConnection;

    protected $table = 'pettycash_ledgers';

    protected $fillable = [
        'date',
        'reference',
        'category',
        'description',
        'amount',
        'direction',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];
}
