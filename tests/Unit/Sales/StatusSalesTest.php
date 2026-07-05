<?php

use App\Enum\Sales\StatusSales;

test('values returns all status strings', function () {
    $values = StatusSales::values();

    expect($values)->toBeArray();
    expect($values)->toContain('paid');
    expect($values)->toContain('cancelled');
    expect($values)->toContain('pending');
    expect($values)->toContain('partial');
    expect($values)->toHaveCount(4);
});

test('enum cases have correct values', function () {
    expect(StatusSales::PAID->value)->toBe('paid');
    expect(StatusSales::CANCELLED->value)->toBe('cancelled');
    expect(StatusSales::PENDING->value)->toBe('pending');
    expect(StatusSales::PARTIAL->value)->toBe('partial');
});
