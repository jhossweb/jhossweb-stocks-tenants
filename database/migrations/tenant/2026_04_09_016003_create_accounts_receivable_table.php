<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();

            $table->string('acr_sls_number', 50);
            $table->foreign('acr_sls_number')
                ->references('sls_number')
                ->on('sale_orders')
                ->onDelete('cascade');

            $table->decimal('acr_total', 10, 4);
            $table->decimal('acr_total_usd', 12, 4)->nullable();
            $table->decimal('acr_balance', 10, 4);
            $table->decimal('acr_balance_usd', 12, 4)->nullable();
            $table->string('acr_status', 20)->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};
