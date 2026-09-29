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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();

            $table->enum('btr_type', ['inflow', 'outflow'])->default('inflow');
            $table->decimal('btr_amount', 15, 4);
            $table->decimal('btr_balance', 15, 4);
            $table->string('btr_reference', 50);
            $table->string('btr_description', 150)->nullable();
            $table->decimal('btr_valorcam', 15, 4)->default(0);
            $table->decimal('btr_diferencial', 12, 4)->default(0);
            $table->string('btr_coin', 10)->nullable();
            $table->string('btr_method', 30)->nullable();
            $table->date('btr_transaction_date')->nullable();

            $table->nullableMorphs('btr_transactionable');

            $table->foreignId('btr_bank_account_id')->constrained('bank_accounts')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
