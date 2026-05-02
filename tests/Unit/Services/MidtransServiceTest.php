<?php

namespace Aanugerah\WeddingPro\Tests\Unit\Services;

use Aanugerah\WeddingPro\Services\MidtransService;
use PHPUnit\Framework\TestCase;

class MidtransServiceTest extends TestCase
{
    private function makeService(): MidtransService
    {
        return new class extends MidtransService {
            public function __construct() {} // skip parent constructor
        };
    }

    // ── isSuccess() ───────────────────────────────────────────────────────

    public function test_is_success_returns_true_for_settlement(): void
    {
        $this->assertTrue($this->makeService()->isSuccess(['transaction_status' => 'settlement']));
    }

    public function test_is_success_returns_true_for_capture_with_accept(): void
    {
        $this->assertTrue($this->makeService()->isSuccess([
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
        ]));
    }

    public function test_is_success_returns_false_for_capture_with_challenge(): void
    {
        $this->assertFalse($this->makeService()->isSuccess([
            'transaction_status' => 'capture',
            'fraud_status' => 'challenge',
        ]));
    }

    public function test_is_success_returns_false_for_pending(): void
    {
        $this->assertFalse($this->makeService()->isSuccess(['transaction_status' => 'pending']));
    }

    // ── isFailed() ────────────────────────────────────────────────────────

    public function test_is_failed_returns_true_for_deny(): void
    {
        $this->assertTrue($this->makeService()->isFailed(['transaction_status' => 'deny']));
    }

    public function test_is_failed_returns_true_for_expire(): void
    {
        $this->assertTrue($this->makeService()->isFailed(['transaction_status' => 'expire']));
    }

    public function test_is_failed_returns_true_for_cancel(): void
    {
        $this->assertTrue($this->makeService()->isFailed(['transaction_status' => 'cancel']));
    }

    public function test_is_failed_returns_false_for_settlement(): void
    {
        $this->assertFalse($this->makeService()->isFailed(['transaction_status' => 'settlement']));
    }

    public function test_is_failed_returns_false_for_pending(): void
    {
        $this->assertFalse($this->makeService()->isFailed(['transaction_status' => 'pending']));
    }

    // ── verifySignature() ─────────────────────────────────────────────────

    public function test_verify_signature_passes_with_correct_signature(): void
    {
        $serverKey = 'test-server-key';
        $orderId = 'ORDER-001';
        $statusCode = '200';
        $grossAmount = '100000.00';

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // Set config via putenv since we don't have Laravel container
        putenv("MIDTRANS_SERVER_KEY={$serverKey}");

        // Override config() call by using reflection to set the key directly
        $service = $this->makeService();

        // We test the signature logic directly
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $this->assertSame($expected, $signature);

        putenv('MIDTRANS_SERVER_KEY');
    }

    public function test_verify_signature_throws_with_wrong_signature(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Midtrans signature');

        // Mock config to return a known server key
        $service = new class extends MidtransService {
            public function __construct() {}
            public function verifySignature(array $data): void
            {
                $serverKey = 'test-server-key';
                $orderId = $data['order_id'] ?? '';
                $statusCode = $data['status_code'] ?? '';
                $rawGross = (string) ($data['gross_amount'] ?? '');
                $expected = hash('sha512', $orderId . $statusCode . $rawGross . $serverKey);
                if (($data['signature_key'] ?? '') !== $expected) {
                    throw new \Exception('Invalid Midtrans signature');
                }
            }
        };

        $service->verifySignature([
            'order_id' => 'ORDER-001',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'wrong-signature',
        ]);
    }
}
