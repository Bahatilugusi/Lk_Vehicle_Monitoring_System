<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'drivers';

    protected $fillable = [
        'user_id',
        'license_number',
        'license_class',
        'license_expiry',
        'years_experience',
        'emergency_contact',
        'emergency_phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry'   => 'date',
            'years_experience' => 'integer',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * The user account that belongs to this driver.
     * Access: $driver->user
     *
     * This gives you the driver's name, email, phone:
     * $driver->user->name
     * $driver->user->email
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All trips this driver has been assigned to.
     * Access: $driver->trips
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Vehicle assignment history.
     * Access: $driver->vehicleAssignments
     */
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    /**
     * The current active vehicle assignment.
     * Access: $driver->currentVehicleAssignment
     */
    public function currentVehicleAssignment(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class)
                    ->whereNull('released_at');
    }

    // =========================================================
    // HELPER METHODS
    // =========================================================

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isOnTrip(): bool
    {
        return $this->status === 'on_trip';
    }

    public function hasExpiredLicense(): bool
    {
        return $this->license_expiry->isPast();
    }
}