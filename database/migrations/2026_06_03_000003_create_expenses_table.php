<?php

use App\Enum\Expense\ExpenseStatus;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exp_category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->text('exp_description');
            $table->decimal('exp_amount', 10, 2);
            $table->date('exp_date');
            $table->enum('exp_status', ExpenseStatus::values())->default(ExpenseStatus::PENDING->value);
            $table->string('exp_coin', 10)->default(TypeCoin::COP->value);
            $table->decimal('exp_valorcam', 10, 4)->default(1);
            $table->date('exp_payment_date')->nullable();
            $table->foreignId('exp_bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
