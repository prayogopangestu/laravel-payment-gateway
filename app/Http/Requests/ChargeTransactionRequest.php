<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargeTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_type' => ['required', 'string', 'max:50'],
            'transaction_details' => ['required', 'array'],
            'transaction_details.order_id' => ['required', 'string', 'max:64'],
            'transaction_details.gross_amount' => ['required', 'numeric', 'min:1'],
            'item_details' => ['nullable', 'array'],
            'customer_details' => ['nullable', 'array'],
            'bank_transfer' => ['nullable', 'array'],
            'credit_card' => ['nullable', 'array'],
            'gopay' => ['nullable', 'array'],
            'qris' => ['nullable', 'array'],
            'shopeepay' => ['nullable', 'array'],
            'cstore' => ['nullable', 'array'],
            'custom_expiry' => ['nullable', 'array'],
            'custom_field1' => ['nullable', 'string', 'max:255'],
            'custom_field2' => ['nullable', 'string', 'max:255'],
            'custom_field3' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
