<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->decimal('acr_total_usd', 12, 4)->nullable()->after('acr_total');
            $table->decimal('acr_balance_usd', 12, 4)->nullable()->after('acr_balance');
        });
    }

    public function down(): void
    {
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->dropColumn(['acr_total_usd', 'acr_balance_usd']);
        });
    }
};
