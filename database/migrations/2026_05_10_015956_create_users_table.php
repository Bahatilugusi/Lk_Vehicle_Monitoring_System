<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the users table.
     * This is the central table of the system — every role
     * (admin, dispatcher, driver, manager) has a record here.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (BIGINT UNSIGNED)

            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('password');

            // Role controls what each user can see and do in the system
            $table->enum('role', ['admin', 'dispatcher', 'driver', 'manager'])
                  ->default('driver');

            // Status lets admins suspend or deactivate accounts without deleting them
            $table->enum('status', ['active', 'inactive', 'suspended'])
                  ->default('active');

            $table->string('profile_photo', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();   // adds created_at and updated_at
            $table->softDeletes();  // adds deleted_at for safe deletion

            // Indexes for frequently searched columns
            $table->index('role');
            $table->index('status');
        });
    }

    /**
     * Reverse this migration (undo it).
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};