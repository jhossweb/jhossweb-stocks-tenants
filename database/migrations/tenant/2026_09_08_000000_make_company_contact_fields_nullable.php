<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los campos de contacto de la empresa colonial eran NOT NULL y bloquean el
     * provisioning SaaS (el alta de empresa no los captura). Se dejan opcionales.
     */
    public function up(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->string('cmp_rif', 20)->nullable()->change();
            $table->text('cmp_address')->nullable()->change();
            $table->string('cmp_phone', 30)->nullable()->change();
            $table->string('cmp_email', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->string('cmp_rif', 20)->change();
            $table->text('cmp_address')->change();
            $table->string('cmp_phone', 30)->change();
            $table->string('cmp_email', 100)->change();
        });
    }
};
