<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An instructor's certifications — one row each, where
     * `instructors.certification` used to hold a single one.
     *
     * The scanned file lives on the row it belongs to, so uploading a new
     * certificate no longer replaces (and deletes) the previous one.
     */
    public function up(): void
    {
        Schema::create('instructor_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('code')->nullable();
            $table->string('file_path')->nullable();
            $table->date('issued_on')->nullable();
            $table->timestamps();

            $table->index('instructor_id');
        });

        // Carry over whatever each instructor already had. A row with only a
        // scanned file and no title still becomes a certification — the file
        // is the record; the title can be filled in after.
        DB::statement('
            insert into instructor_certifications (instructor_id, title, code, file_path, created_at, updated_at)
            select id,
                   case when certification is null or certification = "" then "Certification" else certification end,
                   certificate_code,
                   certificate_file_path,
                   created_at, updated_at
            from instructors
            where (certification is not null and certification != "")
               or (certificate_file_path is not null and certificate_file_path != "")
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_certifications');
    }
};
