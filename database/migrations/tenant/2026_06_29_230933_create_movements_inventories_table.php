<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements_inventories', function (Blueprint $table) {
            $table->id();

            $table->string('min_inv_number', 50);
            $table->foreign('min_inv_number')
                ->references('inv_number')
                ->on('inventories')
                ->onDelete('cascade');

            $table->string('min_trans_type_code', 6);

            $table->enum('min_factor', ['1', '-1']);

            $table->string('min_pro_code', 50);
            $table->foreign('min_pro_code')
                ->references('pro_code')
                ->on('products')
                ->onDelete('cascade');

            $table->foreignId('min_war_id')->constrained('warehouses')->cascadeOnDelete();

            $table->decimal('min_quantity', 10, 4)->default(0);

            $table->foreignId('min_reason_id')->constrained('adjustment_reasons')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements_inventories');
    }
};
