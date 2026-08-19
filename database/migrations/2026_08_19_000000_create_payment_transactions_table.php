<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 64)->unique();
            $table->decimal('gross_amount', 15, 2);
            $table->string('status', 30)->default('pending');
            $table->string('status_code', 10)->nullable();
            $table->string('transaction_status', 50)->nullable();
            $table->string('fraud_status', 50)->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->json('charge_response')->nullable();
            $table->json('notification_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
}
