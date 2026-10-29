<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Применяет миграцию.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique()->comment('Marketplace order id, e.g. ord_7Q2KX9M4');
            $table->string('number')->unique();
            $table->string('status')->index();
            $table->char('currency', 3);

            $table->string('customer_email')->index();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();

            $table->decimal('delivery_price', 12, 2);
            $table->decimal('discount', 12, 2);
            $table->decimal('total', 12, 2);

            $table->string('shipment_carrier')->nullable();
            $table->string('shipment_tracking_number')->nullable();
            $table->string('shipment_tracking_url')->nullable();

            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->dateTime('created_at')->index();
            $table->dateTime('updated_at');
        });
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
