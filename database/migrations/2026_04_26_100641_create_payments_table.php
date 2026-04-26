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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('payment_method', 20); // 'cash', 'mobile_money', 'insurance'
            $table->decimal('amount', 12, 2);
            $table->string('status', 20); // 'pending', 'confirmed', 'failed', 'refunded'
            $table->string('transaction_ref', 100)->nullable(); // Mobile money transaction reference
            $table->foreignId('cashier_id')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('webhook_event_id')->nullable();
            $table->text('notes')->nullable();
            $table->index(['invoice_id', 'status']);
            $table->index('transaction_ref');
            $table->index(['status', 'processed_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
