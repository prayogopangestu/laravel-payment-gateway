<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_charges_transaction_to_midtrans()
    {
        config(['midtrans.server_key' => 'SB-Mid-server-test']);

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
                'status_code' => '201',
                'transaction_id' => 'trx-123',
                'order_id' => 'ORDER-001',
                'gross_amount' => '150000.00',
                'payment_type' => 'bank_transfer',
                'transaction_status' => 'pending',
            ], 201),
        ]);

        $response = $this->postJson('/api/payments/midtrans/charge', [
            'payment_type' => 'bank_transfer',
            'bank_transfer' => ['bank' => 'bni'],
            'transaction_details' => [
                'order_id' => 'ORDER-001',
                'gross_amount' => 150000,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.order_id', 'ORDER-001')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => 'ORDER-001',
            'status' => 'pending',
            'midtrans_transaction_id' => 'trx-123',
        ]);
    }

    public function test_it_handles_successful_midtrans_webhook()
    {
        config(['midtrans.server_key' => 'SB-Mid-server-test']);

        $payload = [
            'order_id' => 'ORDER-002',
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'transaction_status' => 'settlement',
            'transaction_id' => 'trx-456',
            'payment_type' => 'bank_transfer',
            'fraud_status' => 'accept',
        ];

        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'SB-Mid-server-test'
        );

        $response = $this->postJson('/api/payments/midtrans/webhook', $payload);

        $response->assertOk()
            ->assertJsonPath('data.order_id', 'ORDER-002')
            ->assertJsonPath('data.status', 'success');

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => 'ORDER-002',
            'status' => 'success',
            'midtrans_transaction_id' => 'trx-456',
        ]);
    }

    public function test_it_rejects_webhook_with_invalid_signature()
    {
        config(['midtrans.server_key' => 'SB-Mid-server-test']);

        $response = $this->postJson('/api/payments/midtrans/webhook', [
            'order_id' => 'ORDER-003',
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('payment_transactions', [
            'order_id' => 'ORDER-003',
        ]);
    }
}
