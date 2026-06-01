<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettySmsTemplateUsage extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_sms_template_usages';

    protected $fillable = [
        'event_key',
        'template_id',
        'updated_by',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PettySmsTemplate::class, 'template_id');
    }
}
