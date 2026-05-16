<?php

use App\Enum\Sales\StatusSales;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();

            $table->string('sls_number', 50)->unique();
            $table->date('sls_date');

            $table->string('sls_trans_type_code', 6);
            $table->foreign('sls_trans_type_code')
                ->references('trans_type_code')
                ->on('transaction_types')
                ->onDelete('cascade');

            $table->foreignId('sls_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('sls_valorcam', 10, 4);
            $table->enum('sls_coin', TypeCoin::values());
            $table->decimal('sls_subtotal', 10, 4);
            $table->decimal('sls_exento', 10, 4);
            $table->decimal('sls_iva', 10, 4);

            $table->decimal('sls_total', 10, 4);
            $table->string('sls_status', 20)->default(StatusSales::PENDING->value);

            $table->string('sls_reference', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_orders');
    }
};
