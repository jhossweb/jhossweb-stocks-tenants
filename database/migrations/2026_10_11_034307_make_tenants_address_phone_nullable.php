<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El alta pública de empresa (self-service) no solicita dirección ni teléfono,
 * por lo que estas columnas deben ser opcionales. El panel central sí los exige
 * vía Form Request cuando un super admin crea o edita un tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('address')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('address')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }
};
