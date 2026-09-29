<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustment_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('adjr_code', 10)->unique();
            $table->string('adjr_name', 100);
            $table->text('adjr_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustment_reasons');
    }
};
