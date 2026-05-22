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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('pro_conversion_factor', 10, 4)->default(1.0000)->after('pro_und')
                ->comment('Conversion factor for MTS/KG products. E.g., 1 roll = 100m => factor=100');
            $table->decimal('pro_cant_min', 10, 2)->default(0)->after('pro_conversion_factor')
                ->comment('Minimum stock alert threshold');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pro_conversion_factor', 'pro_cant_min']);
        });
    }
};
