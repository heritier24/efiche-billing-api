<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->enum('role', ['admin', 'cashier', 'staff'])->default('staff')->after('phone');
            $table->foreignId('facility_id')->nullable()->after('role')->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true)->after('facility_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'facility_id', 'is_active']);
        });
    }
};
