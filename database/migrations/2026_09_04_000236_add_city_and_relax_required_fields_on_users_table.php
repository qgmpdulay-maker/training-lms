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
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->nullable()->after('organization');
        });

        // Registration no longer collects these for every participant — "agency"
        // (the OCD Regional Office picklist) is now only asked of OCD Personnel,
        // and "organization"/"mobile_number" are no longer asked at all — so the
        // NOT NULL constraints from when they were always required no longer hold.
        Schema::table('users', function (Blueprint $table) {
            $table->string('agency')->nullable()->change();
            $table->string('mobile_number', 11)->nullable()->change();
            $table->string('organization')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('city');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('agency')->nullable(false)->change();
            $table->string('mobile_number', 11)->nullable(false)->change();
            $table->string('organization')->nullable(false)->change();
        });
    }
};
