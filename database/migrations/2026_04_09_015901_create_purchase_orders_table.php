<?php

use App\Enum\Purchases\StatusPurchases;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number', 50)->unique();
            $table->date('invoice_date');

            $table->string('invoice_trans_type_code', 6);
            $table->foreign('invoice_trans_type_code')
                ->references('trans_type_code')
                ->on('transaction_types')
                ->onDelete('cascade');

            $table->foreignId('invoice_supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('invoice_valorcam', 10, 4);
            $table->enum('invoice_coin', TypeCoin::values());
            $table->decimal('invoice_subtotal', 10, 4);
            $table->decimal('invoice_exento', 10, 4);
            $table->decimal('invoice_iva', 10, 4);
            $table->enum('invoice_type_iva', TypeIva::values());
            $table->decimal('invoice_total', 10, 4);
            $table->enum('invoice_status', StatusPurchases::values())->default(StatusPurchases::PENDING->value);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
