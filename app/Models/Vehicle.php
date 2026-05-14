<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicles';

    protected $fillable = [
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'capacity',
        'fuel_type',
        'status',
        'gps_device_id',
        'gps_sim_number',
        'insurance_expiry',
        'last_service_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'insurance_expiry'  => 'date',
            'last_service_date' => 'date',
            'capacity'          => 'integer',
            'year'              => 'integer',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * The admin/user who registered this vehicle.
     * Access: $vehicle->creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All trips this vehicle has been assigned to.
     * Access: $vehicle->trips
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * All GPS coordinate records for this vehicle.
     * Access: $vehicle->gpsPoints
     */
    public function gpsPoints(): HasMany
    {
        return $this->hasMany(GpsTracking::class);
    }

    /**
     * The most recent GPS location of this vehicle.
     * Access: $vehicle->latestPosition
     *
     * This is a very common need — "where is this vehicle right now?"
     * Instead of loading all GPS history, this loads only the last record.
     */
    public function latestPosition(): HasMany
    {
        return $this->hasMany(GpsTracking::class)
                    ->latest('recorded_at')
                    ->limit(1);
    }

    /**
     * Driver assignment history for this vehicle.
     * Access: $vehicle->driverAssignments
     */
    public function driverAssignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    /**
     * The current active driver assignment (released_at is null).
     * Access: $vehicle->currentAssignment
     */
    public function currentAssignment(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class)
                    ->whereNull('released_at');
    }

    // =========================================================
    // HELPER METHODS
    // =========================================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrip(): bool
    {
        return $this->trips()
                    ->where('status', 'in_progress')
                    ->exists();
    }

    public function hasGpsDevice(): bool
    {
        return ! empty($this->gps_device_id);
    }
}