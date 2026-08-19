<?php

return [
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'request_timeout' => (int) env('MIDTRANS_REQUEST_TIMEOUT', 30),
    'sandbox_base_url' => 'https://api.sandbox.midtrans.com',
    'production_base_url' => 'https://api.midtrans.com',
    'charge_path' => '/v2/charge',
];
