<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            // Who created this trip (dispatcher or admin)
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // The driver assigned to this trip
            $table->foreignId('driver_id')
                  ->constrained('drivers')
                  ->restrictOnDelete();

            // The vehicle assigned to this trip
            $table->foreignId('vehicle_id')
                  ->constrained('vehicles')
                  ->restrictOnDelete();

            // Route information
            $table->string('origin');
            $table->string('destination');
            $table->text('notes')->nullable();

            // Scheduling
            $table->dateTime('scheduled_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Trip lifecycle status
            $table->enum('status', [
                'scheduled',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('scheduled');

            // Cancellation reason, filled only if cancelled
            $table->text('cancellation_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for fast querying
            $table->index('status');
            $table->index('driver_id');
            $table->index('vehicle_id');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};