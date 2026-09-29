<?php

use App\Enum\Purchases\StatusPurchases;
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
        Schema::create('accounts_payables', function (Blueprint $table) {
            $table->id();

            $table->string('acp_purchase_number', 50);
            $table->foreign('acp_purchase_number')
                ->references('invoice_number')
                ->on('purchase_orders')
                ->onDelete('cascade');

            $table->decimal('acp_total', 10, 4);
            $table->decimal('acp_total_usd', 12, 4)->nullable();
            $table->decimal('acp_balance', 10, 4);
            $table->decimal('acp_balance_usd', 12, 4)->nullable();
            $table->enum('acp_status', StatusPurchases::values())->default(StatusPurchases::PENDING->value);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_payables');
    }
};
