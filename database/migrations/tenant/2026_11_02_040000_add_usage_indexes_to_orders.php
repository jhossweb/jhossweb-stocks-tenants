<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table): void {
            $table->index(['sls_trans_type_code', 'created_at'], 'sale_orders_type_created_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->index(['invoice_trans_type_code', 'created_at'], 'purchase_orders_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table): void {
            $table->dropIndex('sale_orders_type_created_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropIndex('purchase_orders_type_created_idx');
        });
    }
};
