<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('billing_cycle');
            $table->decimal('price_usd', 10, 2);
            $table->integer('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('limits')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
