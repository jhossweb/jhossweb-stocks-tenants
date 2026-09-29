<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cm_cash_closing_id')->constrained('cash_closings')->cascadeOnDelete();
            $table->string('cm_type', 10); // inflow / outflow
            $table->decimal('cm_amount', 15, 4);
            $table->string('cm_description', 200);
            $table->string('cm_reference', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
