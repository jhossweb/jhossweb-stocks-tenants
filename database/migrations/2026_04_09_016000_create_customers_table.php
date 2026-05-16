<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('clt_name', 100)->unique();
            $table->string('clt_address', 255);
            $table->string('clt_phone', 20)->unique();
            $table->string('clt_email', 100)->unique();
            $table->string('clt_website', 100)->nullable();
            $table->string('clt_logo', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
