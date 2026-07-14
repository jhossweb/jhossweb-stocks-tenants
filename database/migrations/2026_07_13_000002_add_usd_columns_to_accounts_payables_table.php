<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_payables', function (Blueprint $table) {
            $table->decimal('acp_total_usd', 12, 4)->nullable()->after('acp_total');
            $table->decimal('acp_balance_usd', 12, 4)->nullable()->after('acp_balance');
        });
    }

    public function down(): void
    {
        Schema::table('accounts_payables', function (Blueprint $table) {
            $table->dropColumn(['acp_total_usd', 'acp_balance_usd']);
        });
    }
};
