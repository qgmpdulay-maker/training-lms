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
            $table->string('category')->nullable()->after('training_title');
            $table->string('region')->nullable()->after('lgu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn(['category', 'region']);
        });
    }
};
