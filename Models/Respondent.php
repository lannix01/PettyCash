<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Respondent extends Model
{
    use UsesPettyConnection;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FLAGGED = 'flagged';
    public const STATUS_DECOMMISSIONED = 'decommissioned';
    public const CATEGORY_EXECUTIVE = 'Executive';
    public const CATEGORY_TECHNICIAN = 'Technician';
    public const CATEGORY_CONSULTANT = 'Consultant';
    public const CATEGORY_CUSTOMER_SERVICE = 'Customer Service';
    public const CATEGORY_FIBER_TECHNICIAN = 'Fiber Technician';
    public const CATEGORY_VISITOR = 'Visitor';
    public const CATEGORY_TRAINEE = 'Trainee';
    public const CATEGORY_ATTACHEE = 'Attachee';
    public const CATEGORY_INTERN = 'Intern';
    public const CATEGORY_OTHER_STAFF = 'Other Staff';

    protected $table = 'petty_respondents';

    protected $fillable = [
        'name',
        'phone',
        'category',
        'staff_id',
        'status',
        'profile_title',
        'profile_email',
        'profile_location',
        'profile_notes',
        'profile_photo_path',
        'card_public_token',
        'card_file_path',
        'card_png_path',
        'card_generated_at',
        'card_expires_at',
        'card_sms_sent_at',
    ];

    protected $casts = [
        'card_generated_at' => 'datetime',
        'card_expires_at' => 'datetime',
        'card_sms_sent_at' => 'datetime',
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
            self::STATUS_DECOMMISSIONED => 'Decommissioned',
        ];
    }

    public static function normalizeStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            self::STATUS_INACTIVE => self::STATUS_INACTIVE,
            self::STATUS_FLAGGED => self::STATUS_FLAGGED,
            self::STATUS_DECOMMISSIONED => self::STATUS_DECOMMISSIONED,
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

    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @return list<string>
     */
    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_EXECUTIVE,
            self::CATEGORY_TECHNICIAN,
            self::CATEGORY_CONSULTANT,
            self::CATEGORY_CUSTOMER_SERVICE,
            self::CATEGORY_FIBER_TECHNICIAN,
            self::CATEGORY_VISITOR,
            self::CATEGORY_TRAINEE,
            self::CATEGORY_ATTACHEE,
            self::CATEGORY_INTERN,
            self::CATEGORY_OTHER_STAFF,
        ];
    }

    public static function normalizeCategory(?string $category): string
    {
        $value = trim((string) $category);

        foreach (self::categoryOptions() as $option) {
            if (strcasecmp($option, $value) === 0) {
                return $option;
            }
        }

        return self::CATEGORY_OTHER_STAFF;
    }

    /**
     * @return array<string,string>
     */
    public function cardTheme(): array
    {
        return match ($this->category ?: self::CATEGORY_OTHER_STAFF) {
            self::CATEGORY_EXECUTIVE => [
                'name' => 'Executive Gold',
                'accent' => '#d4a72c',
                'accent_dark' => '#7a5600',
                'panel' => '#fff8dd',
                'ink' => '#2b1e00',
                'badge_bg' => '#fff1b8',
            ],
            self::CATEGORY_CONSULTANT => [
                'name' => 'Consultant Navy',
                'accent' => '#1f5eff',
                'accent_dark' => '#0f2f78',
                'panel' => '#eaf1ff',
                'ink' => '#0f172a',
                'badge_bg' => '#dbe7ff',
            ],
            self::CATEGORY_TECHNICIAN, self::CATEGORY_FIBER_TECHNICIAN => [
                'name' => 'Technical Teal',
                'accent' => '#0f9d7a',
                'accent_dark' => '#075e54',
                'panel' => '#e7fbf6',
                'ink' => '#072a24',
                'badge_bg' => '#c8f3e7',
            ],
            self::CATEGORY_CUSTOMER_SERVICE => [
                'name' => 'Service Blue',
                'accent' => '#1570ef',
                'accent_dark' => '#1849a9',
                'panel' => '#ecf3ff',
                'ink' => '#102a56',
                'badge_bg' => '#dbe8ff',
            ],
            self::CATEGORY_VISITOR => [
                'name' => 'Visitor Slate',
                'accent' => '#667085',
                'accent_dark' => '#344054',
                'panel' => '#f2f4f7',
                'ink' => '#101828',
                'badge_bg' => '#e4e7ec',
            ],
            self::CATEGORY_TRAINEE, self::CATEGORY_ATTACHEE, self::CATEGORY_INTERN => [
                'name' => 'Development Coral',
                'accent' => '#ef6820',
                'accent_dark' => '#b93815',
                'panel' => '#fff1eb',
                'ink' => '#4a1d09',
                'badge_bg' => '#ffd8c2',
            ],
            default => [
                'name' => 'Standard Emerald',
                'accent' => '#039855',
                'accent_dark' => '#027a48',
                'panel' => '#ecfdf3',
                'ink' => '#072b1a',
                'badge_bg' => '#c6f0d6',
            ],
        };
    }
}
