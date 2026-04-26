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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->string('visit_type', 50); // 'consultation', 'lab', 'emergency'
            $table->string('status', 20)->default('active'); // 'active', 'completed', 'cancelled'
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
