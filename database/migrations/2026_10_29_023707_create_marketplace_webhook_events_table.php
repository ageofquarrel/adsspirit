<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketplace_webhook_events', function (Blueprint $table) {
            $table->id();
            // Matches X-Webhook-Id / body.id — the key for idempotent delivery.
            $table->string('event_id')->unique();
            $table->string('type');
            $table->json('payload');
            // Filled once the event has been applied to the order state (commit 3).
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_webhook_events');
    }
};
