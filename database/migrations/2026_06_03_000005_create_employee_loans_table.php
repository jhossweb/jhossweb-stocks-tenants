<?php

use App\Enum\Payroll\LoanStatus;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('elo_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('elo_amount', 10, 2);
            $table->decimal('elo_remaining_balance', 10, 2);
            $table->string('elo_coin', 10)->default(TypeCoin::COP->value);
            $table->decimal('elo_valorcam', 10, 4)->default(1);
            $table->text('elo_description')->nullable();
            $table->enum('elo_status', LoanStatus::values())->default(LoanStatus::ACTIVE->value);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
