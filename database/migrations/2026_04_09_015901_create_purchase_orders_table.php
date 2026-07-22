<?php

use App\Enum\Purchases\StatusPurchases;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            $table->decimal('invoice_total', 10, 4);
            $table->enum('invoice_status', StatusPurchases::values())->default(StatusPurchases::PENDING->value);

            $table->string('invoice_reference')->nullable();

            $table->enum('invoice_payment_condition', ['cash', 'credit'])->default('cash');
            $table->integer('invoice_credit_days')->default(0);
            $table->date('invoice_due_date');

            $table->timestamps();

        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE purchase_orders ADD CONSTRAINT po_credit_days_min CHECK (invoice_credit_days >= 0)');
            DB::statement('ALTER TABLE purchase_orders ADD CONSTRAINT po_credit_days_max CHECK (invoice_credit_days <= 365)');
            DB::statement('ALTER TABLE purchase_orders ADD CONSTRAINT po_due_date_check CHECK (invoice_due_date >= invoice_date)');
            DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT po_payment_condition_check CHECK (
                (invoice_payment_condition = 'cash' AND invoice_credit_days = 0)
                OR
                (invoice_payment_condition = 'credit' AND invoice_credit_days > 0 AND invoice_due_date > invoice_date)
            )");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
