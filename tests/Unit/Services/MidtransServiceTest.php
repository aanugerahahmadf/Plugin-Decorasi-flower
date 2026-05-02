<?php

use Aanugerah\WeddingPro\Services\MidtransService;

// Helper untuk buat instance tanpa konstruktor (skip Midtrans config)
function makeMidtransService(): MidtransService
{
    return new class extends MidtransService {
        public function __construct()
        {
            // Skip parent constructor (tidak perlu server key untuk unit test)
        }
    };
}

// ── isSuccess() ───────────────────────────────────────────────────────────

it('isSuccess returns true for settlement status', function () {
    $service = makeMidtransService();

    expect($service->isSuccess(['transaction_status' => 'settlement']))->toBeTrue();
});

it('isSuccess returns true for capture with accept fraud status', function () {
    $service = makeMidtransService();

    expect($service->isSuccess([
        'transaction_status' => 'capture',
        'fraud_status' => 'accept',
    ]))->toBeTrue();
});

it('isSuccess returns false for capture with challenge fraud status', function () {
    $service = makeMidtransService();

    expect($service->isSuccess([
        'transaction_status' => 'capture',
        'fraud_status' => 'challenge',
    ]))->toBeFalse();
});

it('isSuccess returns false for pending status', function () {
    $service = makeMidtransService();

    expect($service->isSuccess(['transaction_status' => 'pending']))->toBeFalse();
});

// ── isFailed() ────────────────────────────────────────────────────────────

it('isFailed returns true for deny status', function () {
    $service = makeMidtransService();

    expect($service->isFailed(['transaction_status' => 'deny']))->toBeTrue();
});

it('isFailed returns true for expire status', function () {
    $service = makeMidtransService();

    expect($service->isFailed(['transaction_status' => 'expire']))->toBeTrue();
});

it('isFailed returns true for cancel status', function () {
    $service = makeMidtransService();

    expect($service->isFailed(['transaction_status' => 'cancel']))->toBeTrue();
});

it('isFailed returns false for settlement status', function () {
    $service = makeMidtransService();

    expect($service->isFailed(['transaction_status' => 'settlement']))->toBeFalse();
});

it('isFailed returns false for pending status', function () {
    $service = makeMidtransService();

    expect($service->isFailed(['transaction_status' => 'pending']))->toBeFalse();
});

// ── verifySignature() ─────────────────────────────────────────────────────

it('verifySignature passes with correct signature', function () {
    $service = makeMidtransService();

    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey]);

    $orderId = 'ORDER-001';
    $statusCode = '200';
    $grossAmount = '100000.00';

    $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

    $data = [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
    ];

    expect(fn () => $service->verifySignature($data))->not->toThrow(Exception::class);
});

it('verifySignature throws with wrong signature', function () {
    $service = makeMidtransService();

    config(['midtrans.server_key' => 'test-server-key']);

    $data = [
        'order_id' => 'ORDER-001',
        'status_code' => '200',
        'gross_amount' => '100000.00',
        'signature_key' => 'wrong-signature',
    ];

    expect(fn () => $service->verifySignature($data))->toThrow(Exception::class, 'Invalid Midtrans signature');
});
