<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            $table->boolean('is_due_immediately')->default(false)->after('ont_merged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            $table->dropColumn('is_due_immediately');
        });
    }
};
