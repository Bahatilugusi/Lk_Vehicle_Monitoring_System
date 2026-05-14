<?php

// app/Http/Resources/AssignmentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'vehicle' => [
                'id'           => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'make'         => $this->vehicle->make,
                'model'        => $this->vehicle->model,
            ],
            'assigned_by'    => $this->assignedBy->name,
            'assigned_at'    => $this->assigned_at->format('Y-m-d H:i:s'),
            'released_at'    => $this->released_at?->format('Y-m-d H:i:s'),
            'release_reason' => $this->release_reason,
            'is_active'      => $this->isActive(),
            'duration'       => $this->released_at
                                    ? $this->assigned_at->diffForHumans($this->released_at, true)
                                    : 'Ongoing',
        ];
    }
}