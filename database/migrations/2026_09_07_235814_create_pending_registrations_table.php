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
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();

            // Same fields RegisteredUserController collects at registration —
            // nothing becomes a real `users` row until a Super Admin approves it.
            $table->string('name');
            $table->unsignedTinyInteger('age');
            $table->string('sex');
            $table->string('participant_type');
            $table->string('agency')->nullable();
            $table->string('city')->nullable();
            $table->string('email')->unique();
            $table->string('password');

            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('status')->default('pending');
            $table->foreignId('approved_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
