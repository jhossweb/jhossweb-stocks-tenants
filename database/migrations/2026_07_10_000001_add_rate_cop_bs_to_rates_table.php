<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->decimal('rate_cop_bs', 10, 4)->default(0)->after('rate_eur');
        });
    }

    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('rate_cop_bs');
        });
    }
};
