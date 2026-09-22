<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Authoritative organization membership, assigned by the Super Admin after
     * a registration is approved.
     *
     * This sits alongside the existing freetext `organization` column, which
     * stays: that one is what the user typed about themselves at signup and is
     * only ever a hint. `organization_id` is the confirmed link, and only it
     * should be trusted to establish that someone speaks for a body.
     *
     * `position` is their role *within* that organization (e.g. "DRRM Focal
     * Person") — deliberately not called "role", which on this model already
     * means the system role: participant / admin / super_admin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('organization')->constrained()->nullOnDelete();
            $table->string('position')->nullable()->after('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'position']);
        });
    }
};
