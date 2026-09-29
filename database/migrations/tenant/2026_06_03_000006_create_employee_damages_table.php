<?php

use App\Enum\Payroll\DamageStatus;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_damages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eda_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('eda_amount', 10, 2);
            $table->decimal('eda_remaining_balance', 10, 2);
            $table->string('eda_coin', 10)->default(TypeCoin::COP->value);
            $table->decimal('eda_valorcam', 10, 4)->default(1);
            $table->text('eda_description');
            $table->enum('eda_status', DamageStatus::values())->default(DamageStatus::ACTIVE->value);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_damages');
    }
};
