<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();

            // One driver profile per user account — enforced by unique()
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->onDelete('cascade'); // If user is deleted, driver profile goes too

            // Professional credentials
            $table->string('license_number', 50)->unique();
            $table->string('license_class', 10);    // C, D, E, etc.
            $table->date('license_expiry');
            $table->unsignedTinyInteger('years_experience')->default(0);

            // Emergency contact information
            $table->string('emergency_contact', 100)->nullable();
            $table->string('emergency_phone', 20)->nullable();

            // Availability status — updated when a trip starts or ends
            $table->enum('status', ['available', 'on_trip', 'off_duty', 'suspended'])
                  ->default('available');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};