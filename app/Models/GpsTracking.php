<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsTracking extends Model
{
    use HasFactory;

    protected $table = 'gps_tracking';

    /**
     * GPS records are never updated — only inserted.
     * Disabling timestamps tells Laravel not to look for
     * updated_at on this table (we only have created_at).
     */
    public $timestamps = false;

    protected $fillable = [
        'trip_id',
        'vehicle_id',
        'latitude',
        'longitude',
        'altitude',
        'speed',
        'heading',
        'accuracy',
        'satellites',
        'battery_level',
        'signal_strength',
        'recorded_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'decimal:8',
            'longitude'       => 'decimal:8',
            'altitude'        => 'decimal:2',
            'speed'           => 'decimal:2',
            'accuracy'        => 'decimal:2',
            'heading'         => 'integer',
            'satellites'      => 'integer',
            'battery_level'   => 'integer',
            'signal_strength' => 'integer',
            'recorded_at'     => 'datetime',
            'created_at'      => 'datetime',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * The trip this GPS point belongs to.
     * Access: $gpsPoint->trip
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * The vehicle this GPS point belongs to.
     * Access: $gpsPoint->vehicle
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // =========================================================
    // HELPER METHODS
    // =========================================================

    /**
     * Check if the vehicle was speeding when this point was recorded.
     * We will make speed limits configurable later.
     */
    public function isOverSpeedLimit(int $limitKph = 80): bool
    {
        return $this->speed !== null && $this->speed > $limitKph;
    }

    /**
     * Return coordinates as a clean array.
     * Useful for passing to Google Maps API.
     */
    public function coordinates(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}