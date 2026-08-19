<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChargeTransactionRequest;
use App\Models\PaymentTransaction;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class MidtransPaymentController extends Controller
{
    private MidtransService $midtrans;

    public function __construct(MidtransService $midtrans)
    {
        $this->midtrans = $midtrans;
    }

    public function charge(ChargeTransactionRequest $request)
    {
        $payload = $request->all();
        $orderId = data_get($payload, 'transaction_details.order_id');
        $grossAmount = data_get($payload, 'transaction_details.gross_amount');

        $transaction = PaymentTransaction::updateOrCreate(
            ['order_id' => $orderId],
            [
                'gross_amount' => $grossAmount,
                'payment_type' => data_get($payload, 'payment_type'),
                'status' => 'pending',
            ]
        );

        $midtransResponse = $this->midtrans->charge($payload);
        $responseBody = $midtransResponse->json() ?: ['raw' => $midtransResponse->body()];
        $state = $midtransResponse->successful()
            ? $this->midtrans->mapTransactionState($responseBody)
            : 'failed';

        $transaction->fill([
            'status' => $state,
            'status_code' => data_get($responseBody, 'status_code'),
            'transaction_status' => data_get($responseBody, 'transaction_status'),
            'fraud_status' => data_get($responseBody, 'fraud_status'),
            'midtrans_transaction_id' => data_get($responseBody, 'transaction_id'),
            'payment_type' => data_get($responseBody, 'payment_type', data_get($payload, 'payment_type')),
            'charge_response' => $responseBody,
            'paid_at' => $this->midtrans->isSuccessState($state) ? now() : $transaction->paid_at,
            'failed_at' => $state === 'failed' ? now() : $transaction->failed_at,
        ])->save();

        return response()->json([
            'message' => $midtransResponse->successful()
                ? 'Midtrans charge transaction processed.'
                : 'Midtrans charge transaction failed.',
            'data' => $transaction->fresh(),
            'midtrans' => $responseBody,
        ], $midtransResponse->status());
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();

        if (! $this->midtrans->isValidNotificationSignature($payload)) {
            return response()->json([
                'message' => 'Invalid Midtrans notification signature.',
            ], 403);
        }

        $orderId = data_get($payload, 'order_id');

        if (! $orderId) {
            return response()->json([
                'message' => 'Missing order_id in Midtrans notification.',
            ], 422);
        }

        $state = $this->midtrans->mapTransactionState($payload);

        $transaction = PaymentTransaction::updateOrCreate(
            ['order_id' => $orderId],
            [
                'gross_amount' => data_get($payload, 'gross_amount', 0),
                'status' => $state,
                'status_code' => data_get($payload, 'status_code'),
                'transaction_status' => data_get($payload, 'transaction_status'),
                'fraud_status' => data_get($payload, 'fraud_status'),
                'midtrans_transaction_id' => data_get($payload, 'transaction_id'),
                'payment_type' => data_get($payload, 'payment_type'),
                'notification_payload' => $payload,
                'paid_at' => $this->midtrans->isSuccessState($state) ? now() : null,
                'failed_at' => $state === 'failed' ? now() : null,
            ]
        );

        return response()->json([
            'message' => 'Midtrans payment notification handled.',
            'data' => $transaction->fresh(),
        ]);
    }

    public function show(string $orderId)
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        return response()->json([
            'data' => $transaction,
        ]);
    }
}
