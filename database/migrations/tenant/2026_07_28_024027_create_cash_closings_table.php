<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->date('cc_date')->unique();
            $table->decimal('cc_opening_balance', 15, 4)->default(0);
            $table->decimal('cc_total_sales', 15, 4)->default(0);
            $table->decimal('cc_total_received', 15, 4)->default(0);
            $table->decimal('cc_total_manual_inflows', 15, 4)->default(0);
            $table->decimal('cc_total_manual_outflows', 15, 4)->default(0);
            $table->decimal('cc_expected_closing', 15, 4)->default(0);
            $table->decimal('cc_counted_amount', 15, 4)->default(0);
            $table->decimal('cc_difference', 15, 4)->default(0);
            $table->string('cc_status', 20)->default('balanced');
            $table->text('cc_notes')->nullable();
            $table->foreignId('cc_closed_by')->constrained('users');
            $table->timestamp('cc_closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
    }
};
