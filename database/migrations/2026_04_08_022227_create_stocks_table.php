<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            $table->string('stk_pro_code', 50);
            $table->foreign("stk_pro_code")->references("pro_code")->on("products")->cascadeOnDelete();

            $table->decimal('stk_cant', 10, 2)->default(0);

            $table->unique(['stk_pro_code', 'stk_war_id'], 'stocks_pro_war_unique');

            $table->foreignId("stk_war_id")->constrained("warehouses")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
