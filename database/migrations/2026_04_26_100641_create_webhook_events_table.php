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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique(); // External event ID
            $table->string('source', 50); // 'efichepay', 'bank', etc.
            $table->string('event_type', 50); // 'PAYMENT_COMPLETE', 'PAYMENT_FAILED'
            $table->json('payload');
            $table->string('status', 20)->default('received'); // 'received', 'processed', 'failed'
            $table->timestamp('processed_at')->nullable();
            $table->index('event_id');
            $table->index(['status', 'created_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
