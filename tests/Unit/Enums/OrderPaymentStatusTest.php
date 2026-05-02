<?php

namespace Aanugerah\WeddingPro\Tests\Unit\Enums;

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;
use PHPUnit\Framework\TestCase;

class OrderPaymentStatusTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertSame('unpaid', OrderPaymentStatus::UNPAID->value);
        $this->assertSame('pending', OrderPaymentStatus::PENDING->value);
        $this->assertSame('partial', OrderPaymentStatus::PARTIAL->value);
        $this->assertSame('paid', OrderPaymentStatus::PAID->value);
        $this->assertSame('failed', OrderPaymentStatus::FAILED->value);
        $this->assertSame('refunded', OrderPaymentStatus::REFUNDED->value);
    }

    public function test_returns_correct_label(): void
    {
        $this->assertSame('Belum Bayar', OrderPaymentStatus::UNPAID->getLabel());
        $this->assertSame('Menunggu Konfirmasi', OrderPaymentStatus::PENDING->getLabel());
        $this->assertSame('DP / Sebagian', OrderPaymentStatus::PARTIAL->getLabel());
        $this->assertSame('Lunas', OrderPaymentStatus::PAID->getLabel());
        $this->assertSame('Gagal', OrderPaymentStatus::FAILED->getLabel());
        $this->assertSame('Refund', OrderPaymentStatus::REFUNDED->getLabel());
    }

    public function test_returns_correct_color(): void
    {
        $this->assertSame('danger', OrderPaymentStatus::UNPAID->getColor());
        $this->assertSame('warning', OrderPaymentStatus::PENDING->getColor());
        $this->assertSame('info', OrderPaymentStatus::PARTIAL->getColor());
        $this->assertSame('success', OrderPaymentStatus::PAID->getColor());
        $this->assertSame('danger', OrderPaymentStatus::FAILED->getColor());
        $this->assertSame('gray', OrderPaymentStatus::REFUNDED->getColor());
    }

    public function test_returns_correct_icon(): void
    {
        $this->assertSame('heroicon-m-x-circle', OrderPaymentStatus::UNPAID->getIcon());
        $this->assertSame('heroicon-m-clock', OrderPaymentStatus::PENDING->getIcon());
        $this->assertSame('heroicon-m-banknotes', OrderPaymentStatus::PARTIAL->getIcon());
        $this->assertSame('heroicon-m-check-circle', OrderPaymentStatus::PAID->getIcon());
        $this->assertSame('heroicon-m-exclamation-circle', OrderPaymentStatus::FAILED->getIcon());
        $this->assertSame('heroicon-m-arrow-path', OrderPaymentStatus::REFUNDED->getIcon());
    }

    public function test_can_be_created_from_string(): void
    {
        $this->assertSame(OrderPaymentStatus::PAID, OrderPaymentStatus::from('paid'));
        $this->assertSame(OrderPaymentStatus::REFUNDED, OrderPaymentStatus::from('refunded'));
    }
}
