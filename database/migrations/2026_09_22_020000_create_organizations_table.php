<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The bodies OCD trains: LGUs, national government agencies, academe, and
     * teams. Super-admin-curated — a participant can never self-join one, so
     * membership here is authoritative and can be trusted when an agency files
     * a training request.
     *
     * One table with a `type` column rather than four near-identical tables.
     * That's a storage decision only: the admin UI keeps the four types
     * distinct (filtered and badged separately), since they are genuinely
     * different kinds of body and shouldn't read as one merged "organization"
     * concept.
     *
     * Named `organizations`, not `agencies`, deliberately: `users.agency`
     * already exists and means the OCD Regional Office picklist shown to OCD
     * Personnel at signup — an `agencies` table beside it would put two
     * unrelated meanings of the word in the same model.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Two LGUs can share a name across regions, but the same body
            // shouldn't be entered twice under one type and region.
            $table->unique(['name', 'type', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
