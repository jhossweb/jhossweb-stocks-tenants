<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_rates', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->date('rate_date')->unique();
            $table->decimal('rate_usd', 15, 4)->default(0);
            $table->decimal('rate_cop', 15, 4)->default(0);
            $table->decimal('rate_eur', 15, 4)->default(0);
            $table->decimal('rate_cop_bs', 15, 4)->default(0);
            $table->timestamps();

            $table->index('rate_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_rates');
    }
};
