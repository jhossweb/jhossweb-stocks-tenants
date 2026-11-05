<?php

declare(strict_types=1);

use App\Enum\Finanzas\TypeReceipt;
use App\Models\AdjustmentReason;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Brand;
use App\Models\CashClosing;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\MovementInventory;
use App\Models\MovementsSale;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Documents\DocumentLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function lifecycleTransactionTypes(): void
{
    foreach (['FACV' => -1, 'COT' => 0, 'DEVV' => 1, 'FAC' => 1, 'DEVC' => -1, 'INV' => 1] as $code => $factor) {
        TransactionType::firstOrCreate(
            ['trans_type_code' => $code],
            ['trans_type_description' => $code, 'trans_type_factor' => $factor],
        );
    }
}

function lifecycleProduct(): array
{
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $warehouse = Warehouse::factory()->create();

    $product = Product::create([
        'pro_code' => 'P-'.fake()->unique()->numerify('####'),
        'pro_name' => 'Producto '.fake()->word(),
        'pro_und' => 'und',
        'pro_iva' => '16',
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
        'pro_type' => 'simple',
    ]);

    return [$product, $warehouse];
}

function setStock(Product $product, Warehouse $warehouse, float $quantity): void
{
    DB::table('stocks')->updateOrInsert(
        ['stk_pro_code' => $product->pro_code, 'stk_war_id' => $warehouse->id],
        ['stk_cant' => $quantity, 'created_at' => now(), 'updated_at' => now()],
    );
}

function currentStock(Product $product, Warehouse $warehouse): float
{
    return (float) (DB::table('stocks')
        ->where('stk_pro_code', $product->pro_code)
        ->where('stk_war_id', $warehouse->id)
        ->value('stk_cant') ?? 0);
}

function makeSaleInvoice(Product $product, Warehouse $warehouse, Customer $customer, float $quantity = 2): SaleOrder
{
    $sale = SaleOrder::factory()->facv()->create([
        'sls_number' => 'FACV-'.fake()->unique()->numerify('#####'),
        'sls_date' => now()->toDateString(),
        'sls_customer_id' => $customer->id,
        'sls_total' => 100,
        'sls_subtotal' => 100,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_coin' => 'USD',
        'sls_status' => 'pending',
        'sls_payment_condition' => 'credit',
        'sls_credit_days' => 30,
        'sls_due_date' => now()->addDays(30)->toDateString(),
    ]);

    MovementsSale::create([
        'mvs_number' => $sale->sls_number,
        'mvs_trans_type_code' => 'FACV',
        'mvs_factor' => -1,
        'mvs_pro_code' => $product->pro_code,
        'mvs_pro_iva' => 0,
        'mvs_war_id' => $warehouse->id,
        'mvs_quantity' => $quantity,
        'mvs_unit_price' => 50,
        'mvs_unit_price_ref' => 50,
    ]);

    return $sale->refresh();
}

beforeEach(function (): void {
    lifecycleTransactionTypes();
});

it('anula una factura de venta sin cobros, revierte stock y conserva la cxc', function (): void {
    [$product, $warehouse] = lifecycleProduct();
    $customer = Customer::factory()->create();
    setStock($product, $warehouse, 10);

    $sale = makeSaleInvoice($product, $warehouse, $customer);

    expect(currentStock($product, $warehouse))->toBe(8.0);

    $arBefore = $sale->accountsReceivable;
    app(DocumentLifecycleService::class)->void($sale, User::factory()->create(), 'Error de facturación');

    expect(currentStock($product, $warehouse))->toBe(10.0)
        ->and($sale->refresh()->voided_at)->not->toBeNull()
        ->and($sale->refresh()->is_active)->toBeFalse();

    $arAfter = $sale->refresh()->accountsReceivable;
    expect((float) $arAfter->acr_balance)->toBe((float) $arBefore->acr_balance);
});

it('bloquea la anulación de una factura con cobros', function (): void {
    [$product, $warehouse] = lifecycleProduct();
    $customer = Customer::factory()->create();
    setStock($product, $warehouse, 10);

    $sale = makeSaleInvoice($product, $warehouse, $customer);
    $ar = $sale->accountsReceivable;

    $bankAccount = BankAccount::factory()->create(['bank_currency' => 'USD', 'bank_balance' => 1000]);
    $bankTx = BankTransaction::create([
        'btr_bank_account_id' => $bankAccount->id,
        'btr_type' => 'inflow',
        'btr_amount' => 100,
        'btr_balance' => 0,
        'btr_reference' => 'REF-1',
        'btr_description' => 'Cobro',
        'btr_valorcam' => 1,
        'btr_diferencial' => 0,
        'btr_coin' => 'USD',
        'btr_method' => 'EFECTIVO',
        'btr_transaction_date' => now()->toDateString(),
    ]);
    $ar->registerPayment(100, TypeReceipt::RECEIPT->value, $bankTx->id);

    expect(fn () => app(DocumentLifecycleService::class)->void($sale->refresh(), User::factory()->create(), 'Motivo'))
        ->toThrow(DomainException::class);
});

it('bloquea la anulación si la fecha tiene cierre de caja cerrado', function (): void {
    [$product, $warehouse] = lifecycleProduct();
    $customer = Customer::factory()->create();
    setStock($product, $warehouse, 10);

    $sale = makeSaleInvoice($product, $warehouse, $customer);
    $user = User::factory()->create();

    CashClosing::create([
        'cc_date' => now()->toDateString(),
        'cc_status' => 'balanced',
        'cc_closed_by' => $user->id,
        'cc_closed_at' => now(),
    ]);

    expect(fn () => app(DocumentLifecycleService::class)->void($sale, $user, 'Motivo'))
        ->toThrow(DomainException::class);
});

it('anula un cobro y restaura la cxc y el banco', function (): void {
    [$product, $warehouse] = lifecycleProduct();
    $customer = Customer::factory()->create();
    setStock($product, $warehouse, 10);

    $sale = makeSaleInvoice($product, $warehouse, $customer);
    $ar = $sale->accountsReceivable;

    $bankAccount = BankAccount::factory()->create(['bank_currency' => 'USD', 'bank_balance' => 1000]);
    $bankTx = BankTransaction::create([
        'btr_bank_account_id' => $bankAccount->id,
        'btr_type' => 'inflow',
        'btr_amount' => 100,
        'btr_balance' => 0,
        'btr_reference' => 'REF-2',
        'btr_description' => 'Cobro',
        'btr_valorcam' => 1,
        'btr_diferencial' => 0,
        'btr_coin' => 'USD',
        'btr_method' => 'EFECTIVO',
        'btr_transaction_date' => now()->toDateString(),
    ]);

    $receipt = $ar->registerPayment(100, TypeReceipt::RECEIPT->value, $bankTx->id);

    expect((float) $ar->refresh()->acr_balance)->toBe(0.0);

    app(DocumentLifecycleService::class)->void($receipt, User::factory()->create(), 'Cobro duplicado');

    expect((float) $ar->refresh()->acr_balance)->toBe(100.0)
        ->and($receipt->refresh()->voided_at)->not->toBeNull()
        ->and((float) $bankTx->refresh()->btr_amount)->toBe(0.0);
});

it('bloquea la anulación de facturas de compra', function (): void {
    $supplier = Supplier::factory()->create();
    $purchase = PurchaseOrder::factory()->create([
        'invoice_trans_type_code' => 'FAC',
        'invoice_supplier_id' => $supplier->id,
    ]);

    expect(fn () => app(DocumentLifecycleService::class)->void($purchase, User::factory()->create(), 'Motivo'))
        ->toThrow(DomainException::class);
});

it('revierte el stock de un ajuste de inventario', function (): void {
    [$product, $warehouse] = lifecycleProduct();
    setStock($product, $warehouse, 5);

    $inventory = Inventory::factory()->forAdjustment()->create();
    MovementInventory::create([
        'min_inv_number' => $inventory->inv_number,
        'min_trans_type_code' => 'INV',
        'min_factor' => 1,
        'min_pro_code' => $product->pro_code,
        'min_war_id' => $warehouse->id,
        'min_quantity' => 2,
        'min_reason_id' => AdjustmentReason::factory()->create()->id,
    ]);

    expect(currentStock($product, $warehouse))->toBe(7.0);

    app(DocumentLifecycleService::class)->void($inventory, User::factory()->create(), 'Ajuste erróneo');

    expect(currentStock($product, $warehouse))->toBe(5.0)
        ->and($inventory->refresh()->voided_at)->not->toBeNull();
});

it('bloquea la anulación de una devolución de venta si no hay stock suficiente', function (): void {
    [$product, $warehouse] = lifecycleProduct();
    $customer = Customer::factory()->create();
    setStock($product, $warehouse, 10);

    $sale = makeSaleInvoice($product, $warehouse, $customer);
    $ar = $sale->accountsReceivable;

    $return = SaleOrder::factory()->devv()->create([
        'sls_number' => 'DEVV-'.fake()->unique()->numerify('#####'),
        'sls_date' => now()->toDateString(),
        'sls_customer_id' => $customer->id,
        'sls_reference' => $sale->sls_number,
        'sls_total' => 100,
        'sls_subtotal' => 100,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_coin' => 'USD',
        'sls_status' => 'pending',
    ]);

    MovementsSale::create([
        'mvs_number' => $return->sls_number,
        'mvs_trans_type_code' => 'DEVV',
        'mvs_factor' => 1,
        'mvs_pro_code' => $product->pro_code,
        'mvs_pro_iva' => 0,
        'mvs_war_id' => $warehouse->id,
        'mvs_quantity' => 2,
        'mvs_unit_price' => 50,
        'mvs_unit_price_ref' => 50,
    ]);

    $ar->registerPayment(100, TypeReceipt::RETURN_CREDIT->value);

    setStock($product, $warehouse, 1);

    expect(fn () => app(DocumentLifecycleService::class)->void($return, User::factory()->create(), 'Motivo'))
        ->toThrow(DomainException::class);

    expect($return->refresh()->voided_at)->toBeNull()
        ->and((float) $ar->refresh()->acr_balance)->toBe(0.0);
});

it('anula una devolución de venta, revierte stock y restaura el crédito', function (): void {
    [$product, $warehouse] = lifecycleProduct();
    $customer = Customer::factory()->create();
    setStock($product, $warehouse, 10);

    $sale = makeSaleInvoice($product, $warehouse, $customer);
    $ar = $sale->accountsReceivable;

    $return = SaleOrder::factory()->devv()->create([
        'sls_number' => 'DEVV-'.fake()->unique()->numerify('#####'),
        'sls_date' => now()->toDateString(),
        'sls_customer_id' => $customer->id,
        'sls_reference' => $sale->sls_number,
        'sls_total' => 100,
        'sls_subtotal' => 100,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_coin' => 'USD',
        'sls_status' => 'pending',
    ]);

    MovementsSale::create([
        'mvs_number' => $return->sls_number,
        'mvs_trans_type_code' => 'DEVV',
        'mvs_factor' => 1,
        'mvs_pro_code' => $product->pro_code,
        'mvs_pro_iva' => 0,
        'mvs_war_id' => $warehouse->id,
        'mvs_quantity' => 2,
        'mvs_unit_price' => 50,
        'mvs_unit_price_ref' => 50,
    ]);

    $ar->registerPayment(100, TypeReceipt::RETURN_CREDIT->value);

    setStock($product, $warehouse, 10);

    app(DocumentLifecycleService::class)->void($return, User::factory()->create(), 'Devolución errónea');

    expect(currentStock($product, $warehouse))->toBe(8.0)
        ->and((float) $ar->refresh()->acr_balance)->toBe(100.0)
        ->and($return->refresh()->voided_at)->not->toBeNull();
});
