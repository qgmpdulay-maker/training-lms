<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An instructor's deployment history — the same shape as
     * `participant_deployments`, which was already a history for graduates
     * while an instructor was stuck with one current deployment.
     *
     * `area` replaces the old habit of editing the instructor's own
     * agency_organization/lgu through the deployment form: where someone was
     * sent is a fact about the deployment, not about the person.
     */
    public function up(): void
    {
        Schema::create('instructor_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->string('deployment');
            $table->date('deployment_date')->nullable();
            $table->string('deployment_role')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();

            $table->index(['instructor_id', 'deployment_date']);
        });

        DB::statement('
            insert into instructor_deployments (instructor_id, deployment, deployment_date, deployment_role, area, created_at, updated_at)
            select id, deployment, deployment_date, deployment_role,
                   case when agency_organization is null or agency_organization = "" then lgu else agency_organization end,
                   created_at, updated_at
            from instructors
            where deployment is not null and deployment != ""
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_deployments');
    }
};
