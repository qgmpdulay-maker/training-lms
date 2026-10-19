<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A training can run for more than one day, which the schema had no way of
 * saying — `preferred_date` was the whole story, so a five-day course looked
 * like a one-day one everywhere it was displayed.
 *
 * Nullable on purpose: a one-day training leaves it empty rather than
 * repeating the start date, and every row that predates this column (every
 * ATAR import among them, whose `date_conducted` is free text like
 * "January 5-7, 2026" that nothing parses) stays valid as a single day.
 * Read it through TrainingRequest::endDate(), which falls back to the start.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('preferred_date');
        });
    }

    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }
};
