<?php

use App\DTOs\Sales\SaleReturn\SaleReturnDTO;

test('fromArray creates DTO with correct values', function () {
    $data = [
        'sls_number' => 'DEV-001',
        'sls_date' => '2026-06-15',
        'sls_customer_id' => '1',
        'sls_valorcam' => '2.5',
        'sls_coin' => 'USD',
        'sls_reference' => 'SLS-001',
        'sls_subtotal' => '100.00',
        'sls_exento' => '10.00',
        'sls_iva' => '16.00',
        'sls_total' => '126.00',
        'sls_payment_condition' => 'cash',
        'sls_credit_days' => 0,
        'sls_due_date' => '2026-06-15',
        'items' => [
            ['mvs_pro_code' => 'PROD-01', 'mvs_quantity' => 2],
        ],
    ];

    $dto = SaleReturnDTO::fromArray($data);

    expect($dto->slsNumber)->toBe('DEV-001');
    expect($dto->slsDate)->toBe('2026-06-15');
    expect($dto->slsCustomerId)->toBe(1);
    expect($dto->slsValorcam)->toBe(2.5);
    expect($dto->slsCoin)->toBe('USD');
    expect($dto->slsReference)->toBe('SLS-001');
    expect($dto->slsSubtotal)->toBe(100.0);
    expect($dto->slsExento)->toBe(10.0);
    expect($dto->slsIva)->toBe(16.0);
    expect($dto->slsTotal)->toBe(126.0);
    expect($dto->items)->toBe($data['items']);
});

test('fromArray handles optional sls_reference', function () {
    $data = [
        'sls_number' => 'DEV-002',
        'sls_date' => '2026-06-15',
        'sls_customer_id' => '2',
        'sls_valorcam' => '1.0',
        'sls_coin' => 'COP',
        'sls_subtotal' => '50.00',
        'sls_exento' => '0.00',
        'sls_iva' => '8.00',
        'sls_total' => '58.00',
        'sls_payment_condition' => 'cash',
        'sls_credit_days' => 0,
        'sls_due_date' => '2026-06-15',
        'items' => [],
    ];

    $dto = SaleReturnDTO::fromArray($data);

    expect($dto->slsReference)->toBeNull();
});

test('toArray returns correct array without items', function () {
    $data = [
        'sls_number' => 'DEV-003',
        'sls_date' => '2026-06-15',
        'sls_customer_id' => '3',
        'sls_valorcam' => '1.0',
        'sls_coin' => 'USD',
        'sls_reference' => 'SLS-003',
        'sls_subtotal' => '200.00',
        'sls_exento' => '20.00',
        'sls_iva' => '32.00',
        'sls_total' => '252.00',
        'sls_payment_condition' => 'cash',
        'sls_credit_days' => 0,
        'sls_due_date' => '2026-06-15',
        'items' => [],
    ];

    $dto = SaleReturnDTO::fromArray($data);
    $array = $dto->toArray();

    expect($array)->toHaveKey('sls_number', 'DEV-003');
    expect($array)->toHaveKey('sls_reference', 'SLS-003');
    expect($array)->not->toHaveKey('items');
    expect($array['sls_customer_id'])->toBe(3);
    expect($array['sls_valorcam'])->toBe(1.0);
});
