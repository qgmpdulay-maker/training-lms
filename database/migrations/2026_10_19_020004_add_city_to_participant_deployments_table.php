<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a graduate was actually sent.
     *
     * Distinct from `users.city`, which is where the graduate is *based* and
     * is what the Graduates by Location map counts them under. A graduate
     * based in Malolos can be deployed to Legazpi; both are worth keeping.
     */
    public function up(): void
    {
        Schema::table('participant_deployments', function (Blueprint $table) {
            $table->string('city')->nullable()->after('deployment_role');
        });
    }

    public function down(): void
    {
        Schema::table('participant_deployments', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
};
