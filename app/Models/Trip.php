<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trips';

    protected $fillable = [
        'trip_code',
        'vehicle_id',
        'driver_id',
        'route_id',
        'origin',
        'destination',
        'dispatched_by',
        'status',
        'scheduled_start',
        'actual_start',
        'actual_end',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'total_distance_km',
        'passengers_count',
        'notes',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start'   => 'datetime',
            'actual_start'      => 'datetime',
            'actual_end'        => 'datetime',
            'start_latitude'    => 'decimal:8',
            'start_longitude'   => 'decimal:8',
            'end_latitude'      => 'decimal:8',
            'end_longitude'     => 'decimal:8',
            'total_distance_km' => 'decimal:2',
            'passengers_count'  => 'integer',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

   /* public function gpsPoints(): HasMany
    {
        return $this->hasMany(GpsTracking::class);
    }

    public function latestGpsPoint(): HasMany
    {
        return $this->hasMany(GpsTracking::class)
                    ->latest('recorded_at')
                    ->limit(1);
    }*/

    // =========================================================
    // STATUS HELPERS
    // =========================================================

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeStarted(): bool
    {
        return $this->status === 'scheduled';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'in_progress';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['scheduled', 'in_progress']);
    }

    // =========================================================
    // COMPUTED HELPERS
    // =========================================================

    /**
     * How long the trip took, in minutes.
     * Returns null if trip hasn't ended yet.
     */
    public function durationInMinutes(): ?int
    {
        if (! $this->actual_start || ! $this->actual_end) {
            return null;
        }

        return (int) $this->actual_start->diffInMinutes($this->actual_end);
    }

    /**
     * Auto-generate a unique trip code.
     * Format: TRP-2025-00001
     */
    public static function generateTripCode(): string
    {
        $year  = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;

        return 'TRP-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}