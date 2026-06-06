<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pde_payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->morphs('pde_deductible');
            $table->decimal('pde_percentage', 5, 2);
            $table->decimal('pde_amount', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_deductions');
    }
};
