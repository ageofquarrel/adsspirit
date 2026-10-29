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
        Schema::create('order_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('type');
            $table->dateTime('sent_at');
            $table->timestamps();

            $table->unique(['order_id', 'type']);
        });
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notifications');
    }
};
