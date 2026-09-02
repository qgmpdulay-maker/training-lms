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
        Schema::table('training_targets', function (Blueprint $table) {
            $table->dropUnique(['training_title', 'category']);
            $table->string('region')->nullable()->after('category');
            $table->unique(['training_title', 'category', 'region']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_targets', function (Blueprint $table) {
            $table->dropUnique(['training_title', 'category', 'region']);
            $table->dropColumn('region');
            $table->unique(['training_title', 'category']);
        });
    }
};
