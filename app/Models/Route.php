<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'routes';

    protected $fillable = [
        'name',
        'origin',
        'destination',
        'distance_km',
        'estimated_time',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'distance_km'    => 'decimal:2',
            'estimated_time' => 'integer',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * The user who created this route.
     * Access: $route->creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All trips that have used this route.
     * Access: $route->trips
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    // =========================================================
    // HELPER METHODS
    // =========================================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Returns estimated time formatted as "1h 30m"
     * instead of raw minutes.
     */
    public function formattedDuration(): string
    {
        if (! $this->estimated_time) {
            return 'Unknown';
        }

        $hours   = intdiv($this->estimated_time, 60);
        $minutes = $this->estimated_time % 60;

        if ($hours === 0) {
            return "{$minutes}m";
        }

        return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
    }
}