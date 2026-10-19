<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The training types one instructor can teach.
     *
     * Until now `instructors.training_type` was a single column, so a person
     * who taught three courses was three near-duplicate instructor rows —
     * which is why the profile page had to gather them back together by name.
     * One instructor is now one row, and this table holds every course they
     * can teach.
     *
     * `rating` lives here rather than on the instructor because L1 feedback
     * is per course; `instructors.rating` stays as their overall average.
     */
    public function up(): void
    {
        Schema::create('instructor_training_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->string('training_type');
            $table->string('certificate_code')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();

            $table->index(['instructor_id', 'training_type']);
            $table->index('training_type');
        });

        // Every existing instructor row carries exactly one training type —
        // carry it over as that instructor's first one.
        DB::statement('
            insert into instructor_training_types (instructor_id, training_type, certificate_code, rating, created_at, updated_at)
            select id, training_type, certificate_code, rating, created_at, updated_at
            from instructors
            where training_type is not null and training_type != ""
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_training_types');
    }
};
