<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_closings', function (Blueprint $table) {
            $table->json('cc_counted_details')->nullable()->after('cc_counted_amount');
            $table->json('cc_differences')->nullable()->after('cc_difference');
            $table->decimal('cc_counted_amount', 15, 4)->nullable()->change();
            $table->decimal('cc_difference', 15, 4)->nullable()->change();
            $table->decimal('cc_expected_closing', 15, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cash_closings', function (Blueprint $table) {
            $table->dropColumn(['cc_counted_details', 'cc_differences']);
            $table->decimal('cc_counted_amount', 15, 4)->nullable(false)->change();
            $table->decimal('cc_difference', 15, 4)->nullable(false)->change();
            $table->decimal('cc_expected_closing', 15, 4)->nullable(false)->change();
        });
    }
};
