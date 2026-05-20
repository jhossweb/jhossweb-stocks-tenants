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
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();

            $table->string('price_pro_code', 50);
            $table->foreign('price_pro_code')->references('pro_code')->on('products')->cascadeOnDelete();

            $table->decimal('price_utility', 10, 2); // Percentage
            $table->decimal('price_usd', 10, 4);    // Calculated price in USD
            $table->date('price_date');
            $table->foreignId('price_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
