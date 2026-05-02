<?php

namespace Aanugerah\WeddingPro\Tests\Unit\Models;

use Aanugerah\WeddingPro\Models\Package;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    // ── getFinalPriceAttribute() ──────────────────────────────────────────

    public function test_returns_discount_price_when_set(): void
    {
        $package = new Package();
        $package->price = 25000000;
        $package->discount_price = 23000000;
        $this->assertSame(23000000.0, $package->final_price);
    }

    public function test_returns_original_price_when_no_discount(): void
    {
        $package = new Package();
        $package->price = 17000000;
        $package->discount_price = null;
        $this->assertSame(17000000.0, $package->final_price);
    }

    public function test_returns_original_price_when_discount_is_zero(): void
    {
        $package = new Package();
        $package->price = 20000000;
        $package->discount_price = 0;
        $this->assertSame(20000000.0, $package->final_price);
    }

    // ── getIsOutOfStockAttribute() ────────────────────────────────────────

    public function test_is_out_of_stock_when_zero(): void
    {
        $package = new Package();
        $package->stock = 0;
        $this->assertTrue($package->is_out_of_stock);
    }

    public function test_is_out_of_stock_when_negative(): void
    {
        $package = new Package();
        $package->stock = -1;
        $this->assertTrue($package->is_out_of_stock);
    }

    public function test_not_out_of_stock_when_positive(): void
    {
        $package = new Package();
        $package->stock = 5;
        $this->assertFalse($package->is_out_of_stock);
    }

    // ── getCategoryColorAttribute() ───────────────────────────────────────

    public function test_returns_package_color_when_set(): void
    {
        $package = new Package();
        $package->color = '#ff0000';
        $this->assertSame('#ff0000', $package->category_color);
    }

    public function test_returns_default_color_when_no_color_and_no_category(): void
    {
        $package = new Package();
        $package->color = null;
        $this->assertSame('#6366f1', $package->category_color);
    }

    // ── getBadgeStyleAttribute() ──────────────────────────────────────────

    public function test_badge_style_contains_gradient(): void
    {
        $package = new Package();
        $package->color = '#3b82f6';
        $this->assertStringContainsString('background: linear-gradient', $package->badge_style);
        $this->assertStringContainsString('#3b82f6', $package->badge_style);
    }

    public function test_badge_style_contains_border_radius(): void
    {
        $package = new Package();
        $package->color = '#10b981';
        $this->assertStringContainsString('border-radius: 99px', $package->badge_style);
    }

    // ── fillable ─────────────────────────────────────────────────────────

    public function test_has_required_fillable_fields(): void
    {
        $fillable = (new Package())->getFillable();
        $this->assertContains('wedding_organizer_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('price', $fillable);
        $this->assertContains('stock', $fillable);
        $this->assertContains('is_featured', $fillable);
    }

    // ── casts ─────────────────────────────────────────────────────────────

    public function test_casts_features_as_array(): void
    {
        $package = new Package();
        $package->setRawAttributes(['features' => json_encode(['Dekorasi Bunga', 'Sound System'])]);
        $this->assertIsArray($package->features);
        $this->assertContains('Dekorasi Bunga', $package->features);
    }

    public function test_casts_is_featured_as_boolean(): void
    {
        $package = new Package();
        $package->setRawAttributes(['is_featured' => '1']);
        $this->assertTrue($package->is_featured);
    }
}
