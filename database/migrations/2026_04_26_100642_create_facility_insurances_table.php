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
        Schema::create('facility_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->decimal('coverage_percentage', 5, 2)->default(100.00);
            $table->decimal('max_claim_amount', 12, 2)->nullable();
            $table->boolean('requires_preauth')->default(false);
            $table->unique(['facility_id', 'insurance_id']);
            $table->index(['facility_id', 'is_active']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_insurances');
    }
};
