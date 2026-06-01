<?php

namespace App\Modules\PettyCash\Support;

final class TokenPaymentStatus
{
    public const DRAFT = 'draft';
    public const SENT_TO_PHONE = 'sent_to_phone';
    public const AWAITING_PAYMENT = 'awaiting_payment';
    public const MPESA_SMS_RECEIVED = 'mpesa_sms_received';
    public const TOKEN_SMS_RECEIVED = 'token_sms_received';
    public const MATCHED = 'matched';
    public const READY_FOR_CONFIRMATION = 'ready_for_confirmation';
    public const CONFIRMED = 'confirmed';
    public const TOKEN_SENT = 'token_sent';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    /**
     * @return array<string,array<int,string>>
     */
    public static function transitions(): array
    {
        return [
            self::DRAFT => [self::SENT_TO_PHONE, self::CANCELLED],
            self::SENT_TO_PHONE => [self::AWAITING_PAYMENT, self::FAILED, self::CANCELLED],
            self::AWAITING_PAYMENT => [self::MPESA_SMS_RECEIVED, self::TOKEN_SMS_RECEIVED, self::FAILED, self::CANCELLED],
            self::MPESA_SMS_RECEIVED => [self::TOKEN_SMS_RECEIVED, self::MATCHED, self::READY_FOR_CONFIRMATION, self::FAILED],
            self::TOKEN_SMS_RECEIVED => [self::MPESA_SMS_RECEIVED, self::MATCHED, self::READY_FOR_CONFIRMATION, self::FAILED],
            self::MATCHED => [self::READY_FOR_CONFIRMATION, self::FAILED],
            self::READY_FOR_CONFIRMATION => [self::CONFIRMED, self::FAILED, self::CANCELLED],
            self::CONFIRMED => [self::TOKEN_SENT, self::FAILED],
            self::TOKEN_SENT => [],
            self::FAILED => [self::SENT_TO_PHONE, self::AWAITING_PAYMENT, self::READY_FOR_CONFIRMATION, self::CANCELLED],
            self::CANCELLED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }
}
