<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            $table->string('inv_number', 50)->unique();
            $table->date('inv_date');

            $table->string('inv_trans_type_code', 6);
            $table->foreign('inv_trans_type_code')
                ->references('trans_type_code')
                ->on('transaction_types')
                ->onDelete('cascade');

            $table->text('inv_notes')->nullable();
            $table->string('inv_status', 20)->default('completed');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
