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
            if (!Schema::hasColumn('petty_notification_settings', 'sms_recipient_map')) {
                $table->json('sms_recipient_map')->nullable()->after('sms_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('petty_notification_settings', 'sms_recipient_map')) {
                $table->dropColumn('sms_recipient_map');
            }
        });
    }
};
