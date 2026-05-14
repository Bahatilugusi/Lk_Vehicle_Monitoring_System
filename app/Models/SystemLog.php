<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    protected $table = 'system_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',  // JSON column → PHP array automatically
            'new_values' => 'array',
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

    // =========================================================
    // STATIC HELPER — Log an action from anywhere in the app
    // =========================================================

    /**
     * Quick way to write a log entry from any controller.
     *
     * Usage:
     * SystemLog::record('vehicle.created', 'vehicles', 'Added Toyota Hiace', $request);
     */
    public static function record(
        string  $action,
        string  $module,
        string  $description = '',
        ?object $request     = null,
        ?array  $oldValues   = null,
        ?array  $newValues   = null
    ): void {
        static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'ip_address'  => $request?->ip(),
            'user_agent'  => $request?->userAgent(),
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'created_at'  => now(),
        ]);
    }
}