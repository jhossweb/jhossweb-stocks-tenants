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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('supplier_name', 100);
            $table->string('supplier_address', 255);
            $table->string('supplier_phone', 20)->unique();
            $table->string('supplier_email', 100)->unique();
            $table->string('supplier_website', 100)->nullable();
            $table->string('supplier_logo', 100)->nullable();
            $table->integer('supplier_credit_days')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
