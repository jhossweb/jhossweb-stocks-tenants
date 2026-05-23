<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components_productos', function (Blueprint $table) {
            $table->id();
            $table->string('cp_pro_code', 50);
            $table->string('cp_comp_pro_code', 50);
            $table->string('cp_pro_und', 10);
            $table->decimal('cp_quantity', 10, 2);
            $table->timestamps();

            $table->unique(['cp_pro_code', 'cp_comp_pro_code']);

            $table->foreign('cp_pro_code')
                ->references('pro_code')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('cp_comp_pro_code')
                ->references('pro_code')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components_productos');
    }
};
