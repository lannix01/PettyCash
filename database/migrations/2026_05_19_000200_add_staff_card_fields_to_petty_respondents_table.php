<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::table('petty_respondents', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_respondents', 'staff_id')) {
                $table->string('staff_id', 60)->nullable()->after('category')->index();
            }
            if (!Schema::hasColumn('petty_respondents', 'card_png_path')) {
                $table->string('card_png_path', 255)->nullable()->after('card_file_path');
            }
            if (!Schema::hasColumn('petty_respondents', 'card_expires_at')) {
                $table->timestamp('card_expires_at')->nullable()->after('card_generated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_respondents', function (Blueprint $table) {
            $drop = [];
            foreach (['staff_id', 'card_png_path', 'card_expires_at'] as $column) {
                if (Schema::hasColumn('petty_respondents', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
