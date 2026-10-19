<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ATAR reports are now written by Regional Admins rather than the Super
     * Admin, so each one has to belong to a region for the Regional Admin's
     * list (and every edit/PDF check) to be scoped to their own.
     *
     * It's stored on the report instead of being read through the linked
     * training because a report written from scratch has no training to read
     * it from — only the author's own region.
     */
    public function up(): void
    {
        Schema::table('atar_reports', function (Blueprint $table) {
            $table->string('region')->nullable()->after('created_by');
        });

        // Backfill the reports that already exist: the linked training's
        // region when generated from one, otherwise the author's region.
        // Anything left null was written from scratch by a Super Admin, who
        // sees every region anyway.
        DB::table('atar_reports')->whereNull('region')->update([
            'region' => DB::raw('(select region from training_requests where training_requests.id = atar_reports.training_request_id)'),
        ]);

        DB::table('atar_reports')->whereNull('region')->update([
            'region' => DB::raw('(select region from users where users.id = atar_reports.created_by)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('atar_reports', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
};
