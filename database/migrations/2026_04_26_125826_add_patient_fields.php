<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('insurance_id')->nullable()->after('address')->constrained()->onDelete('set null');
            $table->date('registration_date')->nullable()->after('insurance_id');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('registration_date');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['insurance_id', 'registration_date', 'status']);
        });
    }
};
