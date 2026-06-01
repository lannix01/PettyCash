<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::table('petty_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_notification_settings', 'email_enabled')) {
                $table->boolean('email_enabled')->default(true)->after('sms_enabled');
            }
            if (!Schema::hasColumn('petty_notification_settings', 'sms_event_map')) {
                $table->json('sms_event_map')->nullable()->after('sms_recipient_map');
            }
            if (!Schema::hasColumn('petty_notification_settings', 'email_event_map')) {
                $table->json('email_event_map')->nullable()->after('sms_event_map');
            }
            if (!Schema::hasColumn('petty_notification_settings', 'sms_role_map')) {
                $table->json('sms_role_map')->nullable()->after('email_event_map');
            }
            if (!Schema::hasColumn('petty_notification_settings', 'email_role_map')) {
                $table->json('email_role_map')->nullable()->after('sms_role_map');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_notification_settings', function (Blueprint $table) {
            foreach (['email_role_map', 'sms_role_map', 'email_event_map', 'sms_event_map', 'email_enabled'] as $column) {
                if (Schema::hasColumn('petty_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
