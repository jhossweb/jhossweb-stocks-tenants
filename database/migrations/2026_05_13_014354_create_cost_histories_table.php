<?php

use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cost_histories', function (Blueprint $table) {
            $table->id();

            $table->string("cost_pro_code", 50);
            $table->foreign('cost_pro_code')->references('pro_code')->on('products')->cascadeOnDelete();

            $table->decimal('cost_pro_cost', 10, 4);
            $table->decimal('cost_pro_cost_ref', 10, 4);
            $table->decimal('cost_valorcam', 10, 4);
            $table->enum('cost_currency', TypeCoin::values())->default(TypeCoin::USD->value);
            $table->date('cost_date');
            $table->decimal("cost_valorcambio", 10, 4)->default(0);
            $table->foreignId('cost_user_id')->constrained('users')->cascadeOnDelete();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_histories');
    }
};
