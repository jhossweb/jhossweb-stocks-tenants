<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('tenant_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('status');
            $table->date('started_at');
            $table->date('trial_ends_at')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->date('canceled_at')->nullable();
            $table->integer('grace_days')->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('plan_id');
            $table->index('status');
            $table->index(['tenant_id', 'status']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
