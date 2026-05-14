<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);            // e.g. "Mbeya CBD — Uyole Market"
            $table->string('origin', 150);
            $table->string('destination', 150);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->unsignedSmallInteger('estimated_time')->nullable(); // In minutes

            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};