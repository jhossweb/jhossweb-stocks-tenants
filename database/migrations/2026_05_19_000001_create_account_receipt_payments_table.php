<?php

use App\Enum\Tesoreria\TypePayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_receipt_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('arp_account_receivable_id')
                ->constrained('accounts_receivable')
                ->cascadeOnDelete();

            $table->foreignId('arp_bank_transaction_id')
                ->constrained('bank_transactions')
                ->cascadeOnDelete();

            $table->decimal('arp_amount', 15, 4)->default(0);
            $table->enum('arp_type', TypePayment::values())->default(TypePayment::PAYMENT->value);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_receipt_payments');
    }
};
