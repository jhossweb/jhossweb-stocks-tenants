<?php

use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('pro_code', 50)->unique();
            $table->string('pro_name', 200)->unique();
            $table->enum('pro_und', UndProduct::values())->default(UndProduct::UND->value);
            $table->enum('pro_iva', TypeIva::values())->default(TypeIva::GN->value);
            $table->foreignId('pro_cat_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('pro_bra_id')->constrained('brands')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
