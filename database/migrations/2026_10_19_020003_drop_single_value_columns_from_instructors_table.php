<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drops the single-value columns whose contents moved into
     * instructor_training_types / instructor_certifications /
     * instructor_deployments in the three migrations before this one.
     *
     * Run after those: leaving these behind would give every one of them a
     * second, silently diverging source of truth.
     *
     * `agency_organization` and `lgu` stay — those describe the person's home
     * organization, not a deployment, and the roster still searches on them.
     */
    public function up(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropColumn([
                'training_type',
                'certification',
                'certificate_code',
                'certificate_file_path',
                'deployment',
                'deployment_date',
                'deployment_role',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->string('training_type')->default('');
            $table->string('certification')->nullable();
            $table->string('certificate_code')->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->string('deployment')->nullable();
            $table->date('deployment_date')->nullable();
            $table->string('deployment_role')->nullable();
        });
    }
};
