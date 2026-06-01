<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::table('petty_gateway_devices', function (Blueprint $table) {
            $table->boolean('smsgate_enabled')->default(false)->after('is_active')->index();
            $table->string('smsgate_mode', 16)->nullable()->after('smsgate_enabled');
            $table->string('smsgate_local_url')->nullable()->after('smsgate_mode');
            $table->string('smsgate_public_url')->nullable()->after('smsgate_local_url');
            $table->string('smsgate_username')->nullable()->after('smsgate_public_url');
            $table->string('smsgate_password')->nullable()->after('smsgate_username');
            $table->string('smsgate_device_id')->nullable()->after('smsgate_password')->index();
            $table->unsignedTinyInteger('smsgate_sim_number')->nullable()->after('smsgate_device_id');
            $table->timestamp('smsgate_last_tested_at')->nullable()->after('smsgate_sim_number')->index();
            $table->string('smsgate_last_error')->nullable()->after('smsgate_last_tested_at');
        });
    }

    public function down(): void
    {
        Schema::table('petty_gateway_devices', function (Blueprint $table) {
            $table->dropColumn([
                'smsgate_enabled',
                'smsgate_mode',
                'smsgate_local_url',
                'smsgate_public_url',
                'smsgate_username',
                'smsgate_password',
                'smsgate_device_id',
                'smsgate_sim_number',
                'smsgate_last_tested_at',
                'smsgate_last_error',
            ]);
        });
    }
};
