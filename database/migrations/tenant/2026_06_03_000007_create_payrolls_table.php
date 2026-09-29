<?php

use App\Enum\Payroll\PayrollStatus;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pay_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('pay_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->decimal('pay_base_salary', 10, 2);
            $table->string('pay_coin', 10)->default(TypeCoin::COP->value);
            $table->decimal('pay_valorcam', 10, 4)->default(1);
            $table->decimal('pay_total_deductions', 10, 2)->default(0);
            $table->decimal('pay_net_salary', 10, 2);
            $table->enum('pay_status', PayrollStatus::values())->default(PayrollStatus::PENDING->value);
            $table->foreignId('pay_bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
