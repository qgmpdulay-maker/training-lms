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
        Schema::create('training_needs_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Raw answers as submitted: [{question, selected, category, points, hours}, ...]
            $table->json('answers');

            // Computed scoring, kept for admin/super-admin statistics
            $table->json('category_scores');
            $table->string('top_category')->nullable();
            $table->unsignedInteger('max_hours')->nullable();

            // Resulting recommendation, denormalized in case the catalog changes later
            $table->string('recommended_training_slug');
            $table->string('recommended_training_title');
            $table->string('recommended_training_category')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_needs_assessments');
    }
};
