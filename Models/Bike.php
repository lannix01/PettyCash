<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;

class Bike extends Model
{
    use UsesPettyConnection;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FLAGGED = 'flagged';
    public const STATUS_DISABLED = 'disabled';

    protected $table = 'petty_bikes';

    protected $fillable = [
        'plate_no',
        'model',
        'status',

        // service tracking
        'last_service_date',
        'next_service_due_date',

        // flags
        'is_unroadworthy',
        'unroadworthy_notes',
        'unroadworthy_at',
        'flagged_at',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'next_service_due_date' => 'date',
        'is_unroadworthy' => 'boolean',
        'unroadworthy_at' => 'datetime',
        'flagged_at' => 'datetime',
    ];

    /**
     * @return array<string,string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_FLAGGED => 'Flagged',
            self::STATUS_DISABLED => 'Disabled',
        ];
    }

    public static function normalizeStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            self::STATUS_INACTIVE => self::STATUS_INACTIVE,
            self::STATUS_FLAGGED => self::STATUS_FLAGGED,
            self::STATUS_DISABLED => self::STATUS_DISABLED,
            default => self::STATUS_ACTIVE,
        };
    }

    public function normalizedStatus(): string
    {
        return self::normalizeStatus((string) $this->status);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->normalizedStatus()] ?? 'Active';
    }

    public function isSelectableForSpending(): bool
    {
        return $this->normalizedStatus() === self::STATUS_ACTIVE;
    }
}
