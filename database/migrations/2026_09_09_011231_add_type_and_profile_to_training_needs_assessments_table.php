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
        Schema::table('training_needs_assessments', function (Blueprint $table) {
            // Distinguishes the generic placeholder quiz from the real,
            // per-participant-type TNA tools (e.g. the Academe PDF tool).
            $table->string('type')->default('generic')->after('user_id');
        });

        Schema::table('training_needs_assessments', function (Blueprint $table) {
            // Non-generic assessments don't recommend a single catalog training.
            $table->string('recommended_training_slug')->nullable()->change();
            $table->string('recommended_training_title')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_needs_assessments', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('training_needs_assessments', function (Blueprint $table) {
            $table->string('recommended_training_slug')->nullable(false)->change();
            $table->string('recommended_training_title')->nullable(false)->change();
        });
    }
};
