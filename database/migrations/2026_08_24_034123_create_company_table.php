<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company', function (Blueprint $table) {
            $table->id();
            $table->string('cmp_name', 150);
            $table->string('cmp_rif', 20);
            $table->text('cmp_address');
            $table->string('cmp_phone', 30);
            $table->string('cmp_email', 100);
            $table->string('cmp_website', 150)->nullable();
            $table->string('cmp_logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company');
    }
};
