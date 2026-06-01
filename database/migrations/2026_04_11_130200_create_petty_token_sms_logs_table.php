<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::create('petty_token_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_request_id')->nullable()->index();
            $table->string('sms_box', 20)->index();
            $table->string('sms_kind', 20)->default('other')->index();
            $table->string('sender')->nullable()->index();
            $table->string('receiver')->nullable()->index();
            $table->longText('sms_body');
            $table->string('parsed_reference')->nullable()->index();
            $table->string('parsed_meter_number')->nullable()->index();
            $table->string('parsed_token')->nullable()->index();
            $table->decimal('parsed_units', 12, 2)->nullable();
            $table->decimal('parsed_amount', 14, 2)->nullable();
            $table->decimal('parsed_token_amount', 14, 2)->nullable();
            $table->decimal('parsed_other_charges', 14, 2)->nullable();
            $table->decimal('parsed_transaction_cost', 14, 2)->nullable();
            $table->string('parsed_payment_type', 20)->nullable()->index();
            $table->timestamp('sms_received_at')->nullable()->index();
            $table->timestamp('sms_sent_at')->nullable()->index();
            $table->decimal('matched_confidence', 5, 2)->nullable()->index();
            $table->boolean('is_matched')->default(false)->index();
            $table->unsignedBigInteger('matched_by_user_id')->nullable()->index();
            $table->timestamp('matched_at')->nullable()->index();
            $table->unsignedBigInteger('gateway_device_id')->nullable()->index();
            $table->timestamps();

            $table->index(['sms_kind', 'sms_received_at'], 'ptsl_kind_received_idx');
            $table->index(['parsed_meter_number', 'parsed_amount'], 'ptsl_meter_amount_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_token_sms_logs');
    }
};
