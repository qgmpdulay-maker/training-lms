<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The participant type was renamed from "Volunteers" to "Volunteer", so
     * accounts and registrations saved under the old name keep routing to the
     * Volunteer TNA and keep their selection in the profile dropdown.
     */
    public function up(): void
    {
        foreach (['users', 'pending_registrations'] as $table) {
            DB::table($table)->where('participant_type', 'Volunteers')->update(['participant_type' => 'Volunteer']);
        }
    }

    public function down(): void
    {
        foreach (['users', 'pending_registrations'] as $table) {
            DB::table($table)->where('participant_type', 'Volunteer')->update(['participant_type' => 'Volunteers']);
        }
    }
};
