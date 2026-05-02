<?php

use Aanugerah\WeddingPro\Models\Package;

// ── getFinalPriceAttribute() ──────────────────────────────────────────────

it('returns discount_price as final price when discount is set', function () {
    $package = new Package();
    $package->price = 25000000;
    $package->discount_price = 23000000;

    expect($package->final_price)->toBe(23000000.0);
});

it('returns original price as final price when no discount', function () {
    $package = new Package();
    $package->price = 17000000;
    $package->discount_price = null;

    expect($package->final_price)->toBe(17000000.0);
});

it('returns original price when discount_price is zero', function () {
    $package = new Package();
    $package->price = 20000000;
    $package->discount_price = 0;

    expect($package->final_price)->toBe(20000000.0);
});

// ── getIsOutOfStockAttribute() ────────────────────────────────────────────

it('returns true when stock is zero', function () {
    $package = new Package();
    $package->stock = 0;

    expect($package->is_out_of_stock)->toBeTrue();
});

it('returns true when stock is negative', function () {
    $package = new Package();
    $package->stock = -1;

    expect($package->is_out_of_stock)->toBeTrue();
});

it('returns false when stock is positive', function () {
    $package = new Package();
    $package->stock = 5;

    expect($package->is_out_of_stock)->toBeFalse();
});

// ── getCategoryColorAttribute() ───────────────────────────────────────────

it('returns package color when set', function () {
    $package = new Package();
    $package->color = '#ff0000';

    expect($package->category_color)->toBe('#ff0000');
});

it('returns default color when no color and no category', function () {
    $package = new Package();
    $package->color = null;

    expect($package->category_color)->toBe('#6366f1');
});

// ── getBadgeStyleAttribute() ──────────────────────────────────────────────

it('badge style contains background gradient', function () {
    $package = new Package();
    $package->color = '#3b82f6';

    expect($package->badge_style)->toContain('background: linear-gradient');
    expect($package->badge_style)->toContain('#3b82f6');
});

it('badge style contains border-radius for pill shape', function () {
    $package = new Package();
    $package->color = '#10b981';

    expect($package->badge_style)->toContain('border-radius: 99px');
});

// ── fillable fields ───────────────────────────────────────────────────────

it('has all required fillable fields', function () {
    $package = new Package();
    $fillable = $package->getFillable();

    expect($fillable)->toContain('wedding_organizer_id');
    expect($fillable)->toContain('name');
    expect($fillable)->toContain('slug');
    expect($fillable)->toContain('price');
    expect($fillable)->toContain('stock');
    expect($fillable)->toContain('is_featured');
});

// ── casts ─────────────────────────────────────────────────────────────────

it('casts features as array', function () {
    $package = new Package();
    $package->setRawAttributes(['features' => json_encode(['Dekorasi Bunga', 'Sound System'])]);

    expect($package->features)->toBeArray();
    expect($package->features)->toContain('Dekorasi Bunga');
});

it('casts is_featured as boolean', function () {
    $package = new Package();
    $package->setRawAttributes(['is_featured' => '1']);

    expect($package->is_featured)->toBeTrue();
});
