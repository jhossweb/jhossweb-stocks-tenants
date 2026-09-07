<?php

use App\Enum\Purchases\StatusPurchases;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    TransactionType::create(['trans_type_code' => 'FAC', 'trans_type_description' => 'Factura Compra', 'trans_type_factor' => '1']);

    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $this->warehouse = Warehouse::factory()->create(['id' => 1]);
    $this->supplier = Supplier::factory()->create();

    $this->product = Product::factory()->create([
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
        'pro_iva' => '16',
    ]);

    Rate::factory()->create([
        'rate_date' => now()->format('Y-m-d'),
        'rate_usd' => 1.0000,
        'rate_cop' => 4000.0000,
        'rate_eur' => 0.9000,
    ]);

    $this->purchaseData = [
        'invoice_number' => 'INV-CREDIT-001',
        'invoice_date' => now()->format('Y-m-d'),
        'invoice_supplier_id' => $this->supplier->id,
        'invoice_valorcam' => 1.0000,
        'invoice_coin' => 'USD',
        'invoice_subtotal' => 100.0000,
        'invoice_exento' => 0.0000,
        'invoice_iva' => 16.0000,
        'invoice_total' => 116.0000,
        'invoice_payment_condition' => 'credit',
        'invoice_credit_days' => 30,
        'invoice_due_date' => now()->addDays(30)->format('Y-m-d'),
        'items' => [
            [
                'mov_pro_code' => $this->product->pro_code,
                'mov_war_id' => $this->warehouse->id,
                'mov_quantity' => 5,
                'mov_unit_cost' => 20.0000,
                'mov_unit_cost_ref' => 50.0000,
                'mov_pro_iva' => 16,
            ],
        ],
    ];
});

test('store a credit purchase without payment data is accepted', function () {
    $this->post(route('purchases.store'), $this->purchaseData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CREDIT-001')->first();

    expect($purchase)->not->toBeNull();
    expect($purchase->invoice_payment_condition)->toBe('credit');
    expect($purchase->invoice_status)->toBe(StatusPurchases::PENDING->value);
});

test('store a credit purchase with empty payments array is accepted', function () {
    $data = array_merge($this->purchaseData, [
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'payments' => [],
        ],
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    expect(PurchaseOrder::where('invoice_number', 'INV-CREDIT-001')->exists())->toBeTrue();
});

test('a stored credit purchase generates a pending payable for the full total', function () {
    $this->post(route('purchases.store'), $this->purchaseData)
        ->assertSessionHasNoErrors();

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CREDIT-001')->first();
    $payable = $purchase->accountsPayable;

    expect($payable)->not->toBeNull();
    expect((float) $payable->acp_balance)->toBe(116.0);
    expect($payable->acp_status)->toBe(StatusPurchases::PENDING->value);
    expect($payable->payments()->count())->toBe(0);
});
