<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('exp_paid_amount', 10, 2)->default(0)->after('exp_amount');
            $table->decimal('exp_remaining_balance', 10, 2)->default(0)->after('exp_paid_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['exp_paid_amount', 'exp_remaining_balance']);
        });
    }
};
