<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class MidtransService
{
    public function charge(array $payload): Response
    {
        $serverKey = config('midtrans.server_key');

        if (! $serverKey) {
            throw new InvalidArgumentException('MIDTRANS_SERVER_KEY is not configured.');
        }

        return Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(config('midtrans.request_timeout'))
            ->post($this->chargeUrl(), $payload);
    }

    public function isValidNotificationSignature(array $payload): bool
    {
        $serverKey = config('midtrans.server_key');
        $signature = data_get($payload, 'signature_key');
        $orderId = data_get($payload, 'order_id');
        $statusCode = data_get($payload, 'status_code');
        $grossAmount = data_get($payload, 'gross_amount');

        if (! $serverKey || ! $signature || ! $orderId || ! $statusCode || ! $grossAmount) {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }

    public function mapTransactionState(array $payload): string
    {
        $transactionStatus = strtolower((string) data_get($payload, 'transaction_status'));
        $fraudStatus = strtolower((string) data_get($payload, 'fraud_status'));

        if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
            return 'challenge';
        }

        if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
            return $fraudStatus && $fraudStatus !== 'accept' ? 'challenge' : 'success';
        }

        if ($transactionStatus === 'pending') {
            return 'pending';
        }

        if (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true)) {
            return 'failed';
        }

        if (in_array($transactionStatus, ['refund', 'partial_refund', 'chargeback', 'partial_chargeback'], true)) {
            return 'refunded';
        }

        return 'unknown';
    }

    public function isSuccessState(string $state): bool
    {
        return $state === 'success';
    }

    private function chargeUrl(): string
    {
        $baseUrl = config('midtrans.is_production')
            ? config('midtrans.production_base_url')
            : config('midtrans.sandbox_base_url');

        return rtrim($baseUrl, '/').config('midtrans.charge_path');
    }
}
