<?php

use App\Enum\Inventary\UndProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('pro_und', UndProduct::values())
                ->default(UndProduct::UND->value)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('pro_und', ['und', 'mts', 'kg'])
                ->default('und')
                ->change();
        });
    }
};
