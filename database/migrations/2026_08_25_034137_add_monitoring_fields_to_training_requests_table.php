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
        Schema::table('training_requests', function (Blueprint $table) {
            $table->string('agency_type')->nullable()->after('region');
            $table->decimal('latitude', 10, 7)->nullable()->after('agency_type');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('teams_organized')->default(0)->after('longitude');
            $table->unsignedInteger('graduates_male')->default(0)->after('teams_organized');
            $table->unsignedInteger('graduates_female')->default(0)->after('graduates_male');
            $table->unsignedInteger('graduates_age_18_30')->default(0)->after('graduates_female');
            $table->unsignedInteger('graduates_age_31_45')->default(0)->after('graduates_age_18_30');
            $table->unsignedInteger('graduates_age_46_59')->default(0)->after('graduates_age_31_45');
            $table->unsignedInteger('graduates_age_60_up')->default(0)->after('graduates_age_46_59');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn([
                'agency_type', 'latitude', 'longitude', 'teams_organized',
                'graduates_male', 'graduates_female',
                'graduates_age_18_30', 'graduates_age_31_45', 'graduates_age_46_59', 'graduates_age_60_up',
            ]);
        });
    }
};
