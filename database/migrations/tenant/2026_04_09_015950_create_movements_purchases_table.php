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
        Schema::create('movements_purchases', function (Blueprint $table) {
            $table->id();

            $table->string('mov_invoice_number', 50);
            $table->foreign('mov_invoice_number')
                ->references('invoice_number')
                ->on('purchase_orders')
                ->onDelete('cascade');

            $table->string('mov_invoice_trans_type_code', 6);

            $table->enum('mov_factor', ['1', '-1']);

            $table->string('mov_pro_code', 50);
            $table->foreign('mov_pro_code')
                ->references('pro_code')
                ->on('products')
                ->onDelete('cascade');
            $table->integer('mov_pro_iva')->default(0);

            $table->foreignId('mov_war_id')->constrained('warehouses')->cascadeOnDelete();

            $table->decimal('mov_quantity', 10, 4)->default(0);
            $table->decimal('mov_unit_cost', 10, 4)->default(0);
            $table->decimal('mov_unit_cost_ref', 10, 4)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements_purchases');
    }
};
