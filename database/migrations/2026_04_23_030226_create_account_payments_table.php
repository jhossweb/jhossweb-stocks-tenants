<?php

use App\Enum\Tesoreria\TypePayment;
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
        Schema::create('account_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('apy_bank_transaction_id')->constrained('bank_transactions')->onDelete('cascade');
            $table->foreignId('apy_account_payable_id')->constrained('accounts_payables')->onDelete('cascade');
            $table->decimal('apy_amount', 15, 4)->default(0);
            $table->enum('apy_type', TypePayment::values())->default(TypePayment::PAYMENT->value);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_payments');
    }
};
