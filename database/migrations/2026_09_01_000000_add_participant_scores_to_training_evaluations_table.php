<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_evaluations', function (Blueprint $table) {
            $table->json('participant_scores')->nullable()->after('module_ratings');
        });
    }

    public function down(): void
    {
        Schema::table('training_evaluations', function (Blueprint $table) {
            $table->dropColumn('participant_scores');
        });
    }
};
