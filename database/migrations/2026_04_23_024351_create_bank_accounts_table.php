<?php

use App\Enum\Tesoreria\TypeAccount;
use App\Enum\TypeCoin;
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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('bank_name', 150)->unique();
            $table->string('bank_account_number', 20)->unique();
            $table->enum('bank_type', TypeAccount::values())->default(TypeAccount::CORRIENTE->value);
            $table->enum('bank_currency', TypeCoin::values())->default(TypeCoin::BS->value);
            $table->boolean('bank_status')->default(true);
            $table->string('bank_description', 150)->nullable();
            $table->decimal('bank_balance', 15, 4)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
