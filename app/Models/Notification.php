<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    /**
     * Notifications are never edited after creation,
     * so we only need created_at — not updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'trip_id',
        'vehicle_id',
        'type',
        'title',
        'message',
        'is_read',
        'read_at',
        'sent_via',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read'    => 'boolean',
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // =========================================================
    // HELPER METHODS
    // =========================================================

    /**
     * Mark this notification as read.
     * Call: $notification->markAsRead()
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}