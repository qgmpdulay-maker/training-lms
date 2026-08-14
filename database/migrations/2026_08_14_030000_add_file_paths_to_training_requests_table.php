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
            $table->string('certificate_file_path')->nullable()->after('certificate_remarks');
            $table->string('atar_file_path')->nullable()->after('certificate_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn(['certificate_file_path', 'atar_file_path']);
        });
    }
};
