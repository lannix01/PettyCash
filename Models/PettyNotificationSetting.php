<?php

namespace App\Modules\PettyCash\Models;

use App\Modules\PettyCash\Support\UsesPettyConnection;
use Illuminate\Database\Eloquent\Model;

class PettyNotificationSetting extends Model
{
    use UsesPettyConnection;

    protected $table = 'petty_notification_settings';

    protected $fillable = [
        'sms_gateway',
        'sms_enabled',
        'email_enabled',
        'sms_recipient_map',
        'sms_event_map',
        'email_event_map',
        'sms_role_map',
        'email_role_map',
        'low_balance_threshold',
        'low_credit_threshold',
        'updated_by',
    ];

    protected $casts = [
        'sms_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_recipient_map' => 'array',
        'sms_event_map' => 'array',
        'email_event_map' => 'array',
        'sms_role_map' => 'array',
        'email_role_map' => 'array',
        'low_balance_threshold' => 'float',
        'low_credit_threshold' => 'float',
    ];

    /**
     * @return array<int,string>
     */
    public static function eventKeys(): array
    {
        return [
            'token_due_3',
            'token_due_2',
            'token_due_1',
            'token_due_today',
            'token_overdue',
            'due_tomorrow_shortfall',
            'low_balance',
            'low_credit',
        ];
    }

    /**
     * @return array<string,bool>
     */
    public static function defaultSmsEventMap(): array
    {
        return collect(self::eventKeys())
            ->mapWithKeys(fn (string $eventKey) => [$eventKey => true])
            ->all();
    }

    /**
     * @return array<string,bool>
     */
    public static function defaultEmailEventMap(): array
    {
        $enabled = [
            'token_due_3',
            'token_due_2',
            'token_due_1',
            'token_due_today',
            'token_overdue',
        ];

        return collect(self::eventKeys())
            ->mapWithKeys(fn (string $eventKey) => [$eventKey => in_array($eventKey, $enabled, true)])
            ->all();
    }

    /**
     * @return array<string,array<int,string>>
     */
    public static function emptyRoleMap(): array
    {
        return collect(self::eventKeys())
            ->mapWithKeys(fn (string $eventKey) => [$eventKey => []])
            ->all();
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'sms_gateway' => 'advanta',
            'sms_enabled' => true,
            'email_enabled' => true,
            'sms_recipient_map' => null,
            'sms_event_map' => self::defaultSmsEventMap(),
            'email_event_map' => self::defaultEmailEventMap(),
            'sms_role_map' => self::emptyRoleMap(),
            'email_role_map' => self::emptyRoleMap(),
            'low_balance_threshold' => 0,
            'low_credit_threshold' => 0,
        ]);
    }
}
