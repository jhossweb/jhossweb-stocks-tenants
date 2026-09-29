<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->unsignedBigInteger('trans_last_number')->default(0);
            $table->string('trans_type_factor', 2)->change();
        });

        Schema::table('movements_sales', function (Blueprint $table) {
            $table->string('mvs_factor', 2)->change();
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('sls_quote_status', 20)->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transaction_types DROP CONSTRAINT IF EXISTS transaction_types_trans_type_factor_check');
            DB::statement('ALTER TABLE movements_sales DROP CONSTRAINT IF EXISTS movements_sales_mvs_factor_check');
        }
    }

    public function down(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->dropColumn('trans_last_number');
            $table->enum('trans_type_factor', ['1', '-1'])->change();
        });

        Schema::table('movements_sales', function (Blueprint $table) {
            $table->enum('mvs_factor', ['1', '-1'])->change();
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn('sls_quote_status');
        });
    }
};
