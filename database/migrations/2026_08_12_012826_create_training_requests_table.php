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
        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number')->nullable()->unique();

            // Training
            $table->string('training_slug');
            $table->string('training_title');

            // Requester / agency details
            $table->string('requesting_agency');
            $table->string('contact_person');
            $table->string('contact_number');
            $table->string('contact_email');
            $table->unsignedInteger('number_of_participants');
            $table->date('preferred_date');
            $table->text('purpose');

            // Training Needs Assessment
            $table->boolean('tna_completed')->default(false);
            $table->string('tna_file_path')->nullable();

            // Logistics acknowledgment (venue, accommodation, materials, honoraria)
            $table->boolean('logistics_acknowledged')->default(false);

            // Signature
            $table->string('signature_name');
            $table->string('signed_letter_path')->nullable();

            $table->string('status')->default('submitted');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_requests');
    }
};
