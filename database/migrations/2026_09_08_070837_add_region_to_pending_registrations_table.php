<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Everyone except OCD Personnel now picks their region directly at
        // registration (OCD Personnel still get theirs from `agency`, see
        // PendingRegistration::regionLabel()).
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->string('region')->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
};
