<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('petty_hostels')) {
            return;
        }

        Schema::table('petty_hostels', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_hostels', 'qr_type')) {
                $table->string('qr_type', 32)->nullable()->after('is_due_immediately');
            }
            if (!Schema::hasColumn('petty_hostels', 'qr_target')) {
                $table->string('qr_target', 128)->nullable()->after('qr_type');
            }
            if (!Schema::hasColumn('petty_hostels', 'qr_reference')) {
                $table->string('qr_reference', 128)->nullable()->after('qr_target');
            }
            if (!Schema::hasColumn('petty_hostels', 'qr_payload')) {
                $table->text('qr_payload')->nullable()->after('qr_reference');
            }
            if (!Schema::hasColumn('petty_hostels', 'qr_amount')) {
                $table->decimal('qr_amount', 13, 2)->default(0)->after('qr_payload');
            }
            if (!Schema::hasColumn('petty_hostels', 'qr_image_path')) {
                $table->string('qr_image_path')->nullable()->after('qr_amount');
            }
            if (!Schema::hasColumn('petty_hostels', 'qr_generated_at')) {
                $table->timestamp('qr_generated_at')->nullable()->after('qr_image_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('petty_hostels')) {
            return;
        }

        Schema::table('petty_hostels', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('petty_hostels', 'qr_type') ? 'qr_type' : null,
                Schema::hasColumn('petty_hostels', 'qr_target') ? 'qr_target' : null,
                Schema::hasColumn('petty_hostels', 'qr_reference') ? 'qr_reference' : null,
                Schema::hasColumn('petty_hostels', 'qr_payload') ? 'qr_payload' : null,
                Schema::hasColumn('petty_hostels', 'qr_amount') ? 'qr_amount' : null,
                Schema::hasColumn('petty_hostels', 'qr_image_path') ? 'qr_image_path' : null,
                Schema::hasColumn('petty_hostels', 'qr_generated_at') ? 'qr_generated_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
