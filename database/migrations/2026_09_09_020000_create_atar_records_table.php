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
        Schema::create('atar_records', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('atar_tracker_code')->nullable();
            $table->string('training_type_code')->nullable();
            $table->string('training_title');
            $table->string('mode_of_implementation')->nullable();
            $table->string('month')->nullable();
            $table->string('date_conducted')->nullable();
            $table->string('venue')->nullable();
            $table->text('issues_and_concerns')->nullable();
            $table->text('ways_forward')->nullable();
            $table->decimal('overall_rating', 3, 2)->nullable();
            $table->boolean('signed')->default(false);
            $table->unsignedInteger('dropouts')->nullable();
            $table->unsignedInteger('participation')->nullable();
            $table->unsignedInteger('graduates')->nullable();
            $table->unsignedInteger('graduates_rdrrmc')->nullable();
            $table->unsignedInteger('graduates_lgu')->nullable();
            $table->unsignedInteger('graduates_ldrrmo')->nullable();
            $table->unsignedInteger('graduates_academe')->nullable();
            $table->unsignedInteger('graduates_cso')->nullable();
            $table->unsignedInteger('graduates_ngo')->nullable();
            $table->unsignedInteger('graduates_volunteer')->nullable();
            $table->unsignedInteger('graduates_private_sector')->nullable();
            $table->unsignedInteger('graduates_others')->nullable();
            $table->unsignedInteger('graduates_male')->nullable();
            $table->unsignedInteger('graduates_female')->nullable();
            $table->unsignedInteger('graduates_pwd')->nullable();
            $table->unsignedInteger('graduates_youth')->nullable();
            $table->string('source_of_funds')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('actual', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->boolean('l1_completed')->default(false);
            $table->boolean('l2_completed')->default(false);
            $table->date('date_atar_submitted')->nullable();
            $table->string('verified_by')->nullable();
            $table->text('remarks')->nullable();
            $table->string('source_file_path')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atar_records');
    }
};
