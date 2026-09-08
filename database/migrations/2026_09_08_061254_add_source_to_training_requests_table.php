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
        // Defaults to 'admin_scheduled' so every existing row (all of which
        // predate the public portal) backfills correctly — only requests
        // filed through PublicTrainingRequestController are 'public_portal'.
        Schema::table('training_requests', function (Blueprint $table) {
            $table->string('source')->default('admin_scheduled')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
