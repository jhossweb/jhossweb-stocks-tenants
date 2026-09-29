<?php

use App\Enum\Sales\StatusSales;
use App\Enum\TypeCoin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            $table->enum('sls_payment_condition', ['cash', 'credit'])->default('cash');
            $table->integer('sls_credit_days')->default(0);
            $table->date('sls_due_date');

            $table->timestamps();

        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sale_orders ADD CONSTRAINT so_credit_days_min CHECK (sls_credit_days >= 0)');
            DB::statement('ALTER TABLE sale_orders ADD CONSTRAINT so_credit_days_max CHECK (sls_credit_days <= 365)');
            DB::statement('ALTER TABLE sale_orders ADD CONSTRAINT so_due_date_check CHECK (sls_due_date >= sls_date)');
            DB::statement("ALTER TABLE sale_orders ADD CONSTRAINT so_payment_condition_check CHECK (
                (sls_payment_condition = 'cash' AND sls_credit_days = 0)
                OR
                (sls_payment_condition = 'credit' AND sls_credit_days > 0 AND sls_due_date > sls_date)
            )");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_orders');
    }
};
