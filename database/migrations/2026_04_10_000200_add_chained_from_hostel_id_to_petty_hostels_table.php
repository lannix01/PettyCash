<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        if (!Schema::hasColumn('petty_hostels', 'chained_from_hostel_id')) {
            Schema::table('petty_hostels', function (Blueprint $table) {
                $table->unsignedBigInteger('chained_from_hostel_id')
                    ->nullable()
                    ->after('agreement_parent_hostel_id')
                    ->index();

                $table->foreign('chained_from_hostel_id')
                    ->references('id')
                    ->on('petty_hostels')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('petty_hostels', 'chained_from_hostel_id')) {
            Schema::table('petty_hostels', function (Blueprint $table) {
                $table->dropForeign(['chained_from_hostel_id']);
                $table->dropColumn('chained_from_hostel_id');
            });
        }
    }
};
