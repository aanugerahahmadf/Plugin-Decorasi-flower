<?php

namespace Aanugerah\WeddingPro\Tests\Unit\Enums;

use Aanugerah\WeddingPro\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertSame('pending', OrderStatus::PENDING->value);
        $this->assertSame('confirmed', OrderStatus::CONFIRMED->value);
        $this->assertSame('completed', OrderStatus::COMPLETED->value);
        $this->assertSame('cancelled', OrderStatus::CANCELLED->value);
    }

    public function test_returns_correct_label(): void
    {
        $this->assertSame('Menunggu', OrderStatus::PENDING->getLabel());
        $this->assertSame('Dikonfirmasi', OrderStatus::CONFIRMED->getLabel());
        $this->assertSame('Selesai', OrderStatus::COMPLETED->getLabel());
        $this->assertSame('Dibatalkan', OrderStatus::CANCELLED->getLabel());
    }

    public function test_returns_correct_color(): void
    {
        $this->assertSame('warning', OrderStatus::PENDING->getColor());
        $this->assertSame('primary', OrderStatus::CONFIRMED->getColor());
        $this->assertSame('success', OrderStatus::COMPLETED->getColor());
        $this->assertSame('danger', OrderStatus::CANCELLED->getColor());
    }

    public function test_returns_correct_icon(): void
    {
        $this->assertSame('heroicon-m-clock', OrderStatus::PENDING->getIcon());
        $this->assertSame('heroicon-m-check-circle', OrderStatus::CONFIRMED->getIcon());
        $this->assertSame('heroicon-m-check-badge', OrderStatus::COMPLETED->getIcon());
        $this->assertSame('heroicon-m-x-circle', OrderStatus::CANCELLED->getIcon());
    }

    public function test_can_be_created_from_string(): void
    {
        $this->assertSame(OrderStatus::PENDING, OrderStatus::from('pending'));
        $this->assertSame(OrderStatus::CANCELLED, OrderStatus::from('cancelled'));
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(OrderStatus::tryFrom('unknown'));
    }
}
