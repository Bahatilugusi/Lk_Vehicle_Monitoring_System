<?php



namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /*
         * $this->whenLoaded() means: "only include this data if the relationship
         * was eager-loaded with ->load() or with()."
         *
         * This prevents accidental N+1 queries — where Laravel fires a separate
         * database query for every single driver in a list just to get one field.
         */
        $activeAssignment = $this->whenLoaded('currentVehicleAssignment', function () {
            $assignment = $this->currentVehicleAssignment->first();

            if (! $assignment) {
                return null;
            }

            return [
                'assignment_id' => $assignment->id,
                'assigned_at'   => $assignment->assigned_at->format('Y-m-d H:i:s'),
                'vehicle' => $assignment->relationLoaded('vehicle') ? [
                    'id'           => $assignment->vehicle->id,
                    'plate_number' => $assignment->vehicle->plate_number,
                    'make'         => $assignment->vehicle->make,
                    'model'        => $assignment->vehicle->model,
                ] : null,
            ];
        });

        return [
            'id'                => $this->id,

            // From the linked user account
            'full_name'         => $this->user->name,
            'email'             => $this->user->email,

            // License block — grouped for clarity in the mobile app
            'license' => [
                'number'      => $this->license_number,
                'class'       => $this->license_class,
                'expiry_date' => $this->license_expiry->format('Y-m-d'),
                'expired'     => $this->hasExpiredLicense(),   // your model method
                'expires_in'  => $this->license_expiry->diffForHumans(),
            ],

            'years_experience'  => $this->years_experience,

            // Status block — what the mobile app uses to show driver state
            'status' => [
                'current'      => $this->status,
                'is_available' => $this->isAvailable(),        // your model method
                'is_on_trip'   => $this->isOnTrip(),           // your model method
            ],

            'emergency_contact' => [
                'name'  => $this->emergency_contact,
                'phone' => $this->emergency_phone,
            ],

            // Current vehicle — null if no active assignment
            'current_vehicle'   => $activeAssignment,

            'registered_at'     => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}