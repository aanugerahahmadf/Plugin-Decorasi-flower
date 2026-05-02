<?php

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;

it('has correct values for all cases', function () {
    expect(OrderPaymentStatus::UNPAID->value)->toBe('unpaid');
    expect(OrderPaymentStatus::PENDING->value)->toBe('pending');
    expect(OrderPaymentStatus::PARTIAL->value)->toBe('partial');
    expect(OrderPaymentStatus::PAID->value)->toBe('paid');
    expect(OrderPaymentStatus::FAILED->value)->toBe('failed');
    expect(OrderPaymentStatus::REFUNDED->value)->toBe('refunded');
});

it('returns correct label for each status', function () {
    expect(OrderPaymentStatus::UNPAID->getLabel())->toBe('Belum Bayar');
    expect(OrderPaymentStatus::PENDING->getLabel())->toBe('Menunggu Konfirmasi');
    expect(OrderPaymentStatus::PARTIAL->getLabel())->toBe('DP / Sebagian');
    expect(OrderPaymentStatus::PAID->getLabel())->toBe('Lunas');
    expect(OrderPaymentStatus::FAILED->getLabel())->toBe('Gagal');
    expect(OrderPaymentStatus::REFUNDED->getLabel())->toBe('Refund');
});

it('returns correct color for each status', function () {
    expect(OrderPaymentStatus::UNPAID->getColor())->toBe('danger');
    expect(OrderPaymentStatus::PENDING->getColor())->toBe('warning');
    expect(OrderPaymentStatus::PARTIAL->getColor())->toBe('info');
    expect(OrderPaymentStatus::PAID->getColor())->toBe('success');
    expect(OrderPaymentStatus::FAILED->getColor())->toBe('danger');
    expect(OrderPaymentStatus::REFUNDED->getColor())->toBe('gray');
});

it('returns correct icon for each status', function () {
    expect(OrderPaymentStatus::UNPAID->getIcon())->toBe('heroicon-m-x-circle');
    expect(OrderPaymentStatus::PENDING->getIcon())->toBe('heroicon-m-clock');
    expect(OrderPaymentStatus::PARTIAL->getIcon())->toBe('heroicon-m-banknotes');
    expect(OrderPaymentStatus::PAID->getIcon())->toBe('heroicon-m-check-circle');
    expect(OrderPaymentStatus::FAILED->getIcon())->toBe('heroicon-m-exclamation-circle');
    expect(OrderPaymentStatus::REFUNDED->getIcon())->toBe('heroicon-m-arrow-path');
});

it('can be created from string value', function () {
    expect(OrderPaymentStatus::from('paid'))->toBe(OrderPaymentStatus::PAID);
    expect(OrderPaymentStatus::from('refunded'))->toBe(OrderPaymentStatus::REFUNDED);
});
