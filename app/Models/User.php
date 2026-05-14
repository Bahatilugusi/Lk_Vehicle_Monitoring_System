<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The database table this model represents.
     * Laravel would guess 'users' automatically, but being
     * explicit makes the code easier to understand.
     */
    protected $table = 'users';

    /**
     * Fields that are allowed to be mass-assigned.
     *
     * Mass assignment means filling multiple fields at once, like:
     * User::create($request->all())
     *
     * Only fields listed here can be filled that way.
     * This protects against attackers injecting unwanted fields.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'profile_photo',
        'last_login_at',
    ];

    /**
     * Fields that are NEVER included in JSON responses.
     * Even if someone calls $user->toArray(), these stay hidden.
     * Critical for API security — never expose passwords or tokens.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Type casting — automatically converts database values
     * into the correct PHP types when you access them.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',   // auto-hashes on assignment
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * A user who is a driver has one driver profile.
     * Access: $user->driver
     */
    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    /**
     * A user (dispatcher/admin) can dispatch many trips.
     * Access: $user->dispatchedTrips
     */
    public function dispatchedTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'dispatched_by');
    }

    /**
     * A user can receive many notifications.
     * Access: $user->notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * A user's actions are recorded in system logs.
     * Access: $user->logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    /**
     * Vehicles this user added to the system.
     * Access: $user->addedVehicles
     */
    public function addedVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'created_by');
    }

    // =========================================================
    // HELPER METHODS
    // These make role checking clean and readable everywhere
    // =========================================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDispatcher(): bool
    {
        return $this->role === 'dispatcher';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}