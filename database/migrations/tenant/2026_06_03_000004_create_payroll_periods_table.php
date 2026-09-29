<?php

use App\Enum\Payroll\PayrollPeriodStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();

            $table->date('pp_start_date');
            $table->date('pp_end_date');
            $table->date('pp_payment_date');
            $table->enum('pp_status', PayrollPeriodStatus::values())->default(PayrollPeriodStatus::PENDING->value);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
