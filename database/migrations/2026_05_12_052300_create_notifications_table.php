<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('trip_id')
                  ->nullable()
                  ->constrained('trips')
                  ->onDelete('set null');

            $table->foreignId('vehicle_id')
                  ->nullable()
                  ->constrained('vehicles')
                  ->onDelete('set null');

            $table->enum('type', [
                'trip_started',
                'trip_completed',
                'trip_cancelled',
                'speed_exceeded',
                'geofence_breach',
                'device_offline',
                'low_battery',
                'maintenance_due',
                'driver_sos'
            ]);

            $table->string('title', 200);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'is_read']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};