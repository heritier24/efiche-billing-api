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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number', 50)->unique();
            $table->string('status', 20)->default('pending'); // 'pending', 'partially_paid', 'paid', 'overdue'
            $table->decimal('total_amount', 12, 2);
            $table->decimal('insurance_coverage', 12, 2)->default(0.00);
            $table->decimal('patient_responsibility', 12, 2)->storedAs('total_amount - insurance_coverage');
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->timestamp('due_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
