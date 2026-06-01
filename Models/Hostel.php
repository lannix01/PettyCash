<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;

class Hostel extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_hostels';

    protected $fillable = [
        'hostel_name',
        'contact_person',
        'ont_site_id',
        'ont_site_sn',
        'agreement_type',
        'agreement_label',
        'agreement_terminated_at',
        'agreement_termination_reason',
        'agreement_termination_notes',
        'agreement_transfer_hostel_id',
        'agreement_parent_hostel_id',
        'chained_from_hostel_id',
        'meter_no',
        'phone_no',
        'no_of_routers',
        'stake',
        'amount_due',
        'ont_merged',
        'is_due_immediately',
        'qr_type',
        'qr_target',
        'qr_reference',
        'qr_payload',
        'qr_amount',
        'qr_image_path',
        'qr_generated_at',
    ];

    protected $casts = [
        'no_of_routers' => 'integer',
        'amount_due' => 'float',
        'ont_merged' => 'boolean',
        'is_due_immediately' => 'boolean',
        'qr_amount' => 'float',
        'qr_generated_at' => 'datetime',
        'agreement_terminated_at' => 'datetime',
    ];

    public function pendingCredits()
    {
        return $this->hasMany(HostelPendingCredit::class, 'hostel_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'hostel_id');
    }

    public function agreementParent()
    {
        return $this->belongsTo(self::class, 'agreement_parent_hostel_id');
    }

    public function agreementChildren()
    {
        return $this->hasMany(self::class, 'agreement_parent_hostel_id');
    }

    public function chainedFromHostel()
    {
        return $this->belongsTo(self::class, 'chained_from_hostel_id');
    }

    public function chainedChildren()
    {
        return $this->hasMany(self::class, 'chained_from_hostel_id');
    }
}
