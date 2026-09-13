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
        // These fields describe who requested a training and why — they
        // don't apply to a training that entered the system as a bulk
        // historical import (e.g. the ATAR/Training Database import) rather
        // than through the request workflow. Same rationale as nulling
        // user_id in 2026_09_08_055131_make_user_id_nullable_on_training_requests_table.php.
        Schema::table('training_requests', function (Blueprint $table) {
            $table->string('requesting_agency')->nullable()->change();
            $table->string('contact_person')->nullable()->change();
            $table->string('contact_number')->nullable()->change();
            $table->string('contact_email')->nullable()->change();
            $table->text('purpose')->nullable()->change();
            $table->string('signature_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->string('requesting_agency')->nullable(false)->change();
            $table->string('contact_person')->nullable(false)->change();
            $table->string('contact_number')->nullable(false)->change();
            $table->string('contact_email')->nullable(false)->change();
            $table->text('purpose')->nullable(false)->change();
            $table->string('signature_name')->nullable(false)->change();
        });
    }
};
