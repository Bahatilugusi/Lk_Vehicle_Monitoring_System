<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();

            // Nullable — some system events have no user (automated processes)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->string('action', 100);      // e.g. 'vehicle.created', 'trip.started'
            $table->string('module', 50);        // e.g. 'vehicles', 'trips', 'drivers'
            $table->text('description')->nullable();

            $table->string('ip_address', 45)->nullable();  // 45 chars supports IPv6
            $table->string('user_agent', 500)->nullable();

            // Store before and after states as JSON — powerful for audit trails
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('action');
            $table->index('module');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};