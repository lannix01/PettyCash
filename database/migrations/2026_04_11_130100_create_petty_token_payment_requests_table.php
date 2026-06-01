<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::create('petty_token_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payment_type', 20)->index();
            $table->string('paybill_number', 20)->index();
            $table->string('meter_number', 40)->index();
            $table->decimal('amount', 14, 2);
            $table->string('receiver_phone', 40)->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->unsignedBigInteger('hostel_id')->nullable()->index();
            $table->unsignedBigInteger('gateway_device_id')->nullable()->index();
            $table->string('status', 40)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->longText('outgoing_sms_body')->nullable();
            $table->string('operator_initials', 12)->nullable();
            $table->boolean('requires_manual_confirmation')->default(true);
            $table->unsignedBigInteger('matched_mpesa_sms_log_id')->nullable()->index();
            $table->unsignedBigInteger('matched_kplc_sms_log_id')->nullable()->index();
            $table->unsignedBigInteger('outgoing_customer_sms_log_id')->nullable()->index();
            $table->timestamp('sent_to_phone_at')->nullable()->index();
            $table->timestamp('payment_started_at')->nullable();
            $table->timestamp('mpesa_received_at')->nullable();
            $table->timestamp('token_received_at')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('token_sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'ptpr_status_created_idx');
            $table->index(['meter_number', 'amount'], 'ptpr_meter_amount_idx');
            $table->index(['payment_type', 'meter_number', 'created_at'], 'ptpr_type_meter_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_token_payment_requests');
    }
};
