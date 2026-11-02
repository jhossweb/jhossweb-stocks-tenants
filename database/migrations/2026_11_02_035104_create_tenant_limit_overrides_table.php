<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_limit_overrides', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('key');
            $table->unsignedInteger('value');
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_limit_overrides');
    }
};
