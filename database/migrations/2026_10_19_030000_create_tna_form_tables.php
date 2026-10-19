<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Training Needs Assessment questions, moved out of config files and
 * hardcoded view arrays so the Super Admin can edit them (decided 2026-10-19).
 *
 * One tna_form per participant type — Academe, N&RDRRMC, LGU, Volunteer, plus
 * the fallback used by types CDTI has not mapped yet — each with its own
 * ordered questions, and per-option scoring (category/points/hours) that feeds
 * the training recommendation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tna_forms', function (Blueprint $table) {
            $table->id();
            // Stable machine key, matching TrainingNeedsAssessment::TYPE_* for
            // the forms that already existed so old submissions keep their type.
            $table->string('key')->unique();
            $table->string('label');
            // Which participant_type (users.participant_type) this form serves.
            // Null = the fallback form, used when a type has no form of its own.
            $table->string('participant_type')->nullable()->index();
            $table->text('description')->nullable();
            // An inactive form falls back to the default one, so a half-finished
            // edit is never what a participant is asked to fill in.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tna_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tna_form_id')->constrained('tna_forms')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            // Section heading this question sits under (the N&RDRRMC tool groups
            // its competency facets by DRRM domain); null = no heading.
            $table->string('group_label')->nullable();
            $table->text('prompt');
            $table->text('help_text')->nullable();
            // single_choice scores one option; multi_choice is the checklist
            // style (every ticked option scores); competency is the official
            // tools' "rate your competency 1-5 and its importance 1-5" pair,
            // whose gap is what drives a training need; short_text and number
            // collect an answer without scoring it.
            $table->string('kind')->default('single_choice');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['tna_form_id', 'position']);
        });

        Schema::create('tna_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tna_question_id')->constrained('tna_questions')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->text('label');
            // Scoring: points go to this training category, and hours caps how
            // long a recommended training may run. Both optional — an option can
            // simply record an answer.
            $table->string('category')->nullable();
            $table->unsignedInteger('points')->nullable();
            $table->unsignedInteger('hours')->nullable();
            $table->timestamps();

            $table->index(['tna_question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tna_options');
        Schema::dropIfExists('tna_questions');
        Schema::dropIfExists('tna_forms');
    }
};
