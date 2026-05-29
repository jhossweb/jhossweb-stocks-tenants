<?php

use App\Enum\Finanzas\TypeReceipt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rec_account_receivable_id')
                ->constrained('accounts_receivable')
                ->cascadeOnDelete();

            $table->foreignId('rec_bank_transaction_id')
                ->constrained('bank_transactions')
                ->cascadeOnDelete();

            $table->decimal('rec_amount', 15, 4)->default(0);
            $table->enum('rec_type', TypeReceipt::values())->default(TypeReceipt::RECEIPT->value);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
