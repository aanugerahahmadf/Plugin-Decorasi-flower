<?php

use Aanugerah\WeddingPro\Models\Order;
use Aanugerah\WeddingPro\Enums\OrderStatus;
use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;

// ── getEventDateAttribute() ───────────────────────────────────────────────

it('returns event_date formatted as Y-m-d', function () {
    $order = new Order();
    $order->setRawAttributes(['booking_date' => '2026-08-17']);

    expect($order->event_date)->toBe('2026-08-17');
});

it('event_date handles Carbon instance', function () {
    $order = new Order();
    $order->setRawAttributes(['booking_date' => '2026-12-25']);

    expect($order->event_date)->toBe('2026-12-25');
});

// ── fillable ──────────────────────────────────────────────────────────────

it('has all required fillable fields', function () {
    $order = new Order();
    $fillable = $order->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('package_id');
    expect($fillable)->toContain('order_number');
    expect($fillable)->toContain('total_price');
    expect($fillable)->toContain('status');
    expect($fillable)->toContain('payment_status');
    expect($fillable)->toContain('booking_date');
});

// ── casts ─────────────────────────────────────────────────────────────────

it('casts status to OrderStatus enum', function () {
    $order = new Order();
    $order->setRawAttributes(['status' => 'pending']);

    expect($order->status)->toBe(OrderStatus::PENDING);
});

it('casts payment_status to OrderPaymentStatus enum', function () {
    $order = new Order();
    $order->setRawAttributes(['payment_status' => 'paid']);

    expect($order->payment_status)->toBe(OrderPaymentStatus::PAID);
});

it('casts total_price as decimal', function () {
    $order = new Order();
    $order->setRawAttributes(['total_price' => '25000000.00']);

    expect($order->total_price)->toBe('25000000.00');
});

// ── appends ───────────────────────────────────────────────────────────────

it('has event_date in appends', function () {
    $order = new Order();

    expect($order->getAppends())->toContain('event_date');
});
