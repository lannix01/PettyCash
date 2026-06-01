<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::create('petty_gateway_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('device_uuid')->unique();
            $table->string('phone_number')->nullable()->index();
            $table->string('api_token', 120)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->string('app_version')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_gateway_devices');
    }
};
