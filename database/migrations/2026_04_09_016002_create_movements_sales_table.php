<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movements_sales', function (Blueprint $table) {
            $table->id();

            $table->string('mvs_number', 50);
            $table->foreign('mvs_number')
                ->references('sls_number')
                ->on('sale_orders')
                ->onDelete('cascade');

            $table->string('mvs_trans_type_code', 6);

            $table->enum('mvs_factor', ['1', '-1']);

            $table->string('mvs_pro_code', 50);
            $table->foreign('mvs_pro_code')
                ->references('pro_code')
                ->on('products')
                ->onDelete('cascade');
            $table->integer('mvs_pro_iva')->default(0);

            $table->foreignId('mvs_war_id')->constrained('warehouses')->cascadeOnDelete();

            $table->decimal('mvs_quantity', 10, 4)->default(0);
            $table->decimal('mvs_unit_price', 10, 4)->default(0);
            $table->decimal('mvs_unit_price_ref', 10, 4)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements_sales');
    }
};
