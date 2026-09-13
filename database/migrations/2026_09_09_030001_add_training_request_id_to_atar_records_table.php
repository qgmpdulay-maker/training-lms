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
        Schema::table('atar_records', function (Blueprint $table) {
            $table->foreignId('training_request_id')->nullable()->after('region')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atar_records', function (Blueprint $table) {
            $table->dropForeign(['training_request_id']);
            $table->dropColumn('training_request_id');
        });
    }
};
