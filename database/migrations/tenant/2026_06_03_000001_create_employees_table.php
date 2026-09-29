<?php

use App\Enum\Employees\EmployeeStatus;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('emp_name', 100);
            $table->string('emp_document', 20)->unique();
            $table->string('emp_phone', 20)->unique();
            $table->string('emp_email', 100)->unique();
            $table->string('emp_address', 200);
            $table->string('emp_position', 100);
            $table->date('emp_hire_date');
            $table->decimal('emp_base_salary', 10, 2);
            $table->string('emp_salary_coin', 10)->default(TypeCoin::COP->value);
            $table->enum('emp_status', EmployeeStatus::values())->default(EmployeeStatus::ACTIVE->value);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
