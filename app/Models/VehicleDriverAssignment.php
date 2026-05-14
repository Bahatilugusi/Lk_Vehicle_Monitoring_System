<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDriverAssignment extends Model
{
    use HasFactory;

    protected $table = 'vehicle_driver_assignments';

    /**
     * This table uses assigned_at as its timestamp,
     * not the standard created_at/updated_at pair.
     * We disable automatic timestamps and manage them manually.
     */
    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'assigned_by',
        'assigned_at',
        'released_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // =========================================================
    // HELPER METHODS
    // =========================================================

    /**
     * Is this assignment currently active?
     * An active assignment has no released_at date.
     */
    public function isActive(): bool
    {
        return $this->released_at === null;
    }

    /**
     * Release (end) this assignment.
     * Call: $assignment->release()
     */
    public function release(): void
    {
        $this->update(['released_at' => now()]);
    }
}

