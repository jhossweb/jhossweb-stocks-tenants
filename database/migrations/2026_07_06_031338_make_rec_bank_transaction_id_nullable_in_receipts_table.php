<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rec_bank_transaction_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('rec_bank_transaction_id')
                ->nullable()
                ->constrained('bank_transactions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rec_bank_transaction_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('rec_bank_transaction_id')
                ->constrained('bank_transactions')
                ->cascadeOnDelete();
        });
    }
};
