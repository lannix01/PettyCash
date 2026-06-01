<?php

use App\Modules\PettyCash\Models\Respondent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pettycash';

    public function up(): void
    {
        Schema::table('petty_respondents', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_respondents', 'status')) {
                $table->string('status', 40)
                    ->default(Respondent::STATUS_ACTIVE)
                    ->after('category')
                    ->index();
            }
        });

        DB::connection($this->connection)
            ->table('petty_respondents')
            ->whereNull('status')
            ->update(['status' => Respondent::STATUS_ACTIVE]);
    }

    public function down(): void
    {
        Schema::table('petty_respondents', function (Blueprint $table) {
            if (Schema::hasColumn('petty_respondents', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
