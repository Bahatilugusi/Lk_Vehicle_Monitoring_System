<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            // Core vehicle identity
            $table->string('registration_number', 20)->unique();
            $table->string('make', 50);        // Manufacturer: Toyota, Isuzu, etc.
            $table->string('model', 50);       // Specific model: Hiace, NPR, etc.
            $table->year('year');
            $table->string('color', 30)->nullable();
            $table->unsignedTinyInteger('capacity')->default(14); // Passenger capacity

            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid'])
                  ->default('diesel');

            $table->enum('status', ['active', 'maintenance', 'retired', 'unassigned'])
                  ->default('unassigned');

            // GPS hardware identifiers — this links the physical device to this record
            $table->string('gps_device_id', 100)->nullable()->unique();
            $table->string('gps_sim_number', 20)->nullable();

            // Operational dates
            $table->date('insurance_expiry')->nullable();
            $table->date('last_service_date')->nullable();

            // Track who added this vehicle to the system
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->onDelete('restrict'); // Can't delete user if they created vehicles

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('gps_device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};