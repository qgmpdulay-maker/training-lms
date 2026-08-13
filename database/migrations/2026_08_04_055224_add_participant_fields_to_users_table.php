<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->after('name');
            $table->string('sex')->after('age');
            $table->string('picture')->nullable()->after('sex');
            $table->string('participant_type')->after('picture');
            $table->string('organization')->after('participant_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['age', 'sex', 'picture', 'participant_type', 'organization']);
        });
    }
};