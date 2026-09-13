<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atar_reports', function (Blueprint $table) {
            $table->id();
            // Null when the report was written from scratch (not generated
            // from a tracked training). created_by is who clicked "New ATAR".
            $table->foreignId('training_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Header table fields — title/venue/date/funding source, copied
            // from the linked TrainingRequest when generated, or typed by
            // hand otherwise.
            $table->string('title')->nullable();
            $table->string('venue')->nullable();
            $table->string('date_range')->nullable();
            $table->string('funding_source')->nullable();

            // Free-text narrative sections — never auto-generated (see
            // AtarReportGenerator's class doc for why), always admin-written.
            $table->longText('background')->nullable();
            $table->longText('objectives')->nullable();
            $table->longText('attendees_narrative')->nullable();
            $table->longText('highlights')->nullable();
            $table->longText('issues_and_concerns')->nullable();
            $table->longText('ways_forward')->nullable();
            $table->longText('graduates_summary')->nullable();

            // Repeatable-row sections, stored as JSON arrays of {field: value}
            // rows and edited via the Alpine-driven tables on the edit form.
            $table->json('photos')->nullable();
            $table->json('graduates_list')->nullable();
            $table->json('dropouts_list')->nullable();
            $table->json('lecturers_list')->nullable();
            $table->json('signatories')->nullable();

            // Evaluation annexes: l1_modules holds one row per module with
            // its 1-5 rating distribution + average; l2_stats holds the
            // pretest/posttest mean/median/mode/min/max/count pair. Each has
            // its own free-text *_analysis paragraph alongside the numbers.
            $table->json('l1_modules')->nullable();
            $table->longText('l1_analysis')->nullable();
            $table->json('l2_stats')->nullable();
            $table->longText('l2_analysis')->nullable();

            $table->string('status')->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atar_reports');
    }
};
