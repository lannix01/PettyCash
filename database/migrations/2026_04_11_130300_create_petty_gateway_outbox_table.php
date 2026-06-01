<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::create('petty_gateway_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gateway_device_id')->index();
            $table->unsignedBigInteger('payment_request_id')->nullable()->index();
            $table->string('command_type', 40)->index();
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('queued_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_gateway_outbox');
    }
};
