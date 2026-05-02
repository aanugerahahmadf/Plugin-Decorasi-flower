<?php

use Aanugerah\WeddingPro\Enums\OrderStatus;

it('has correct values for all cases', function () {
    expect(OrderStatus::PENDING->value)->toBe('pending');
    expect(OrderStatus::CONFIRMED->value)->toBe('confirmed');
    expect(OrderStatus::COMPLETED->value)->toBe('completed');
    expect(OrderStatus::CANCELLED->value)->toBe('cancelled');
});

it('returns correct label for each status', function () {
    expect(OrderStatus::PENDING->getLabel())->toBe('Menunggu');
    expect(OrderStatus::CONFIRMED->getLabel())->toBe('Dikonfirmasi');
    expect(OrderStatus::COMPLETED->getLabel())->toBe('Selesai');
    expect(OrderStatus::CANCELLED->getLabel())->toBe('Dibatalkan');
});

it('returns correct color for each status', function () {
    expect(OrderStatus::PENDING->getColor())->toBe('warning');
    expect(OrderStatus::CONFIRMED->getColor())->toBe('primary');
    expect(OrderStatus::COMPLETED->getColor())->toBe('success');
    expect(OrderStatus::CANCELLED->getColor())->toBe('danger');
});

it('returns correct icon for each status', function () {
    expect(OrderStatus::PENDING->getIcon())->toBe('heroicon-m-clock');
    expect(OrderStatus::CONFIRMED->getIcon())->toBe('heroicon-m-check-circle');
    expect(OrderStatus::COMPLETED->getIcon())->toBe('heroicon-m-check-badge');
    expect(OrderStatus::CANCELLED->getIcon())->toBe('heroicon-m-x-circle');
});

it('can be created from string value', function () {
    expect(OrderStatus::from('pending'))->toBe(OrderStatus::PENDING);
    expect(OrderStatus::from('cancelled'))->toBe(OrderStatus::CANCELLED);
});

it('returns null for unknown value via tryFrom', function () {
    expect(OrderStatus::tryFrom('unknown'))->toBeNull();
});
