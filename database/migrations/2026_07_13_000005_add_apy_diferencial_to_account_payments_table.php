<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_payments', function (Blueprint $table) {
            $table->decimal('apy_diferencial', 12, 4)->default(0)->after('apy_amount');
        });
    }

    public function down(): void
    {
        Schema::table('account_payments', function (Blueprint $table) {
            $table->dropColumn('apy_diferencial');
        });
    }
};
