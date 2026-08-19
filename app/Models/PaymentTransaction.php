<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'gross_amount',
        'status',
        'status_code',
        'transaction_status',
        'fraud_status',
        'midtrans_transaction_id',
        'payment_type',
        'charge_response',
        'notification_payload',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'charge_response' => 'array',
        'notification_payload' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
