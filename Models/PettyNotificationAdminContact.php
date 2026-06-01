<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;

class PettyNotificationAdminContact extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_notification_admin_contacts';

    protected $fillable = [
        'name',
        'role',
        'phone_no',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
