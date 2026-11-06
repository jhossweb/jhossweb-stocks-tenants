<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfers', function (Blueprint $table): void {
            $table->id();

            $table->string('bt_number', 50)->nullable()->unique();
            $table->date('bt_date');

            $table->foreignId('bt_source_bank_account_id')->constrained('bank_accounts');
            $table->foreignId('bt_destination_bank_account_id')->constrained('bank_accounts');

            $table->decimal('bt_amount', 15, 4);
            $table->string('bt_coin', 10);

            $table->string('bt_reference', 50)->nullable();
            $table->string('bt_description', 500)->nullable();

            $table->foreignId('bt_created_by')->nullable()->constrained('users');

            $table->timestamp('bt_voided_at')->nullable();
            $table->foreignId('bt_voided_by')->nullable()->constrained('users');
            $table->string('bt_void_reason', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfers');
    }
};
