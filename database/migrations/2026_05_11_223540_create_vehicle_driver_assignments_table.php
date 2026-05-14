<?php

// database/migrations/xxxx_create_vehicle_driver_assignments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_driver_assignments', function (Blueprint $table) {
            $table->id();

            /*
             * Which driver is being assigned?
             * If the driver is deleted (soft), we keep this record.
             * restrictOnDelete() prevents deleting a driver who has assignment history.
             * We use cascade here because if a driver is force-deleted,
             * their assignment history goes with them.
             */
            $table->foreignId('driver_id')
                  ->constrained('drivers')
                  ->onDelete('cascade');

            /*
             * Which vehicle is being assigned?
             * If a vehicle is deleted, we keep the assignment record for history.
             */
            $table->foreignId('vehicle_id')
                  ->constrained('vehicles')
                  ->onDelete('cascade');

            // Who made this assignment? (admin or dispatcher)
            $table->foreignId('assigned_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            // When the assignment started
            $table->timestamp('assigned_at')->useCurrent();

            /*
             * When the driver was released from this vehicle.
             * NULL means this is the CURRENT active assignment.
             * This is the key field — your model uses whereNull('released_at')
             * to find the active assignment.
             */
            $table->timestamp('released_at')->nullable();

            // Optional reason for release (e.g., "Trip completed", "Reassigned")
            $table->string('release_reason')->nullable();

            $table->timestamps();

            // Indexes for fast queries
            $table->index('driver_id');
            $table->index('vehicle_id');
            $table->index('released_at');

            /*
             * A driver can only have ONE active assignment at a time.
             * This unique index enforces that at the database level —
             * the strongest possible guarantee.
             *
             * Two rows with same driver_id are only allowed if
             * released_at is NOT NULL (meaning past assignments).
             */
            $table->unique(['driver_id', 'released_at'], 'unique_active_driver_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_driver_assignments');
    }
};