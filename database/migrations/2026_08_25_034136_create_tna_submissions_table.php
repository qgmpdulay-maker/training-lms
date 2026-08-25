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
        Schema::create('tna_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('agency_type')->nullable();
            $table->string('organization')->nullable();
            $table->string('training_topic');
            $table->unsignedInteger('personnel_assessed')->default(0);
            $table->date('date_assessed');
            $table->string('submitted_by');
            $table->string('status')->default('pending');
            $table->string('results_pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tna_submissions');
    }
};
