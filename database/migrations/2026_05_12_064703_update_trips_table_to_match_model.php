<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {

            // ── Rename columns to match the model ──────────────────
            // 'created_by' in migration → 'dispatched_by' in model
            $table->renameColumn('created_by', 'dispatched_by');

            // Scheduling column renames
            $table->renameColumn('scheduled_at', 'scheduled_start');
            $table->renameColumn('started_at',   'actual_start');
            $table->renameColumn('completed_at', 'actual_end');

            // ── Add missing columns ────────────────────────────────

            // Unique trip identifier e.g. TRP-2025-00001
            $table->string('trip_code')->unique()->nullable()->after('id');

            // Route this trip follows
            $table->foreignId('route_id')
                  ->nullable()
                  ->after('vehicle_id')
                  ->constrained('routes')
                  ->nullOnDelete();

            // GPS coordinates at trip start and end points
            $table->decimal('start_latitude',  10, 8)->nullable()->after('notes');
            $table->decimal('start_longitude', 11, 8)->nullable()->after('start_latitude');
            $table->decimal('end_latitude',    10, 8)->nullable()->after('start_longitude');
            $table->decimal('end_longitude',   11, 8)->nullable()->after('end_latitude');

            // Trip statistics
            $table->decimal('total_distance_km', 8, 2)->nullable()->after('end_longitude');
            $table->unsignedInteger('passengers_count')->nullable()->after('total_distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Reverse renames
            $table->renameColumn('dispatched_by',  'created_by');
            $table->renameColumn('scheduled_start', 'scheduled_at');
            $table->renameColumn('actual_start',    'started_at');
            $table->renameColumn('actual_end',      'completed_at');

            // Drop added columns
            $table->dropForeign(['route_id']);
            $table->dropColumn([
                'trip_code',
                'route_id',
                'start_latitude',
                'start_longitude',
                'end_latitude',
                'end_longitude',
                'total_distance_km',
                'passengers_count',
            ]);
        });
    }
};