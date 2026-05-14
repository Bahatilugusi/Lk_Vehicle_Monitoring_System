<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_tracking', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trip_id')
                  ->constrained('trips')
                  ->onDelete('cascade');

            $table->foreignId('vehicle_id')
                  ->constrained('vehicles')
                  ->onDelete('cascade');

            // GPS coordinates — precision to ~1.1mm accuracy
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('altitude', 8, 2)->nullable();   // Meters above sea level

            // Movement data from the GPS device
            $table->decimal('speed', 5, 2)->nullable();       // km/h
            $table->unsignedSmallInteger('heading')->nullable(); // Direction 0-360°
            $table->decimal('accuracy', 6, 2)->nullable();    // GPS accuracy in meters

            // Device health data
            $table->unsignedTinyInteger('satellites')->nullable();   // Satellites in view
            $table->unsignedTinyInteger('battery_level')->nullable(); // Device battery %
            $table->unsignedTinyInteger('signal_strength')->nullable(); // GSM signal bar

            // The exact time the GPS device recorded this position
            // (may differ slightly from when our server received it)
            $table->timestamp('recorded_at')->useCurrent();

            // Only created_at — GPS records are never modified, only inserted
            $table->timestamp('created_at')->useCurrent();

            // Composite indexes for the most common query patterns
            $table->index(['trip_id', 'recorded_at']);      // "Show path of trip X"
            $table->index(['vehicle_id', 'recorded_at']);  // "Where is vehicle Y now?"
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_tracking');
    }
};