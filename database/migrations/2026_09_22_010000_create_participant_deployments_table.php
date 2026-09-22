<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operational deployments of a graduate after they finish a training —
     * e.g. sent to Mayon Operations as Team Leader. Deliberately a history
     * (one row per deployment) rather than a status column: a graduate can be
     * deployed repeatedly, and past deployments are worth keeping.
     *
     * Mirrors the deployment/deployment_date/deployment_role fields Instructor
     * already carries, so the two read the same way; separate table because a
     * participant can have many, where an instructor has one current one.
     *
     * This is the single thing a Regional Admin may write — everything else
     * about trainings and certificates is Super Admin only.
     */
    public function up(): void
    {
        Schema::create('participant_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('deployment');
            $table->date('deployment_date');
            $table->string('deployment_role')->nullable();
            $table->text('notes')->nullable();
            // Which admin entered this, kept for accountability since regional
            // staff record these for their own region. Nulled rather than
            // cascaded if that admin's account is later removed — the
            // deployment itself is still a fact worth keeping.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'deployment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_deployments');
    }
};
