<?php

namespace Aanugerah\WeddingPro\Tests\Unit\Models;

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;
use Aanugerah\WeddingPro\Enums\OrderStatus;
use Aanugerah\WeddingPro\Models\Order;
use Orchestra\Testbench\TestCase;

class OrderTest extends TestCase
{
    public function test_event_date_formatted_as_y_m_d(): void
    {
        $order = new Order();
        $order->setRawAttributes(['booking_date' => '2026-08-17']);
        $this->assertSame('2026-08-17', $order->event_date);
    }

    public function test_has_required_fillable_fields(): void
    {
        $fillable = (new Order())->getFillable();
        $this->assertContains('user_id', $fillable);
        $this->assertContains('package_id', $fillable);
        $this->assertContains('order_number', $fillable);
        $this->assertContains('total_price', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('payment_status', $fillable);
        $this->assertContains('booking_date', $fillable);
    }

    public function test_casts_status_to_order_status_enum(): void
    {
        $order = new Order();
        $order->setRawAttributes(['status' => 'pending']);
        $this->assertSame(OrderStatus::PENDING, $order->status);
    }

    public function test_casts_payment_status_to_enum(): void
    {
        $order = new Order();
        $order->setRawAttributes(['payment_status' => 'paid']);
        $this->assertSame(OrderPaymentStatus::PAID, $order->payment_status);
    }

    public function test_has_event_date_in_appends(): void
    {
        $this->assertContains('event_date', (new Order())->getAppends());
    }
}
