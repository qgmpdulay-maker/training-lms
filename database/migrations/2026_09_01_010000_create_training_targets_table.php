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
        Schema::create('training_targets', function (Blueprint $table) {
            $table->id();
            $table->string('training_title');
            $table->string('category')->default('ta');
            $table->unsignedInteger('target')->default(0);
            $table->timestamps();

            $table->unique(['training_title', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_targets');
    }
};
