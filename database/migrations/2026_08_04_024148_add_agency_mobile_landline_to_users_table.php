<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('agency')->after('name');
            $table->string('mobile_number', 11)->after('agency');
            $table->string('landline_number', 8)->nullable()->after('mobile_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['agency', 'mobile_number', 'landline_number']);
        });
    }
};
