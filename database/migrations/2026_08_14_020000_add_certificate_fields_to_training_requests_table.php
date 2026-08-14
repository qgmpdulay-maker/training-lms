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
        Schema::table('training_requests', function (Blueprint $table) {
            $table->string('lgu')->nullable()->after('requesting_agency');
            $table->string('certificate_code')->nullable()->after('status');
            $table->string('certificate_remarks')->nullable()->after('certificate_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn(['lgu', 'certificate_code', 'certificate_remarks']);
        });
    }
};
