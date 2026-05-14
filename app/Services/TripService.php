<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TripService
{
    /**
     * Create a new scheduled trip.
     */
    public function createTrip(array $data): Trip
    {
        $this->ensureDriverIsAvailable($data['driver_id']);
        $this->ensureVehicleIsAvailable($data['vehicle_id']);

        // If a route is selected, pull origin/destination from it
        if (! empty($data['route_id'])) {
            $route               = Route::findOrFail($data['route_id']);
            $data['origin']      = $route->origin;
            $data['destination'] = $route->destination;
        }

        return DB::transaction(function () use ($data) {
            return Trip::create([
                'trip_code'       => Trip::generateTripCode(),
                'dispatched_by'   => Auth::id(),
                'driver_id'       => $data['driver_id'],
                'vehicle_id'      => $data['vehicle_id'],
                'route_id'        => $data['route_id'] ?? null,
                'scheduled_start' => $data['scheduled_start'],
                'passengers_count'=> $data['passengers_count'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'status'          => 'scheduled',
            ]);
        });
    }

    /**
     * Update a scheduled trip's basic information.
     */
    public function updateTrip(Trip $trip, array $data): Trip
    {
        if (! $trip->isScheduled()) {
            throw new \Exception('Only scheduled trips can be edited.');
        }

        // If route is being changed, update origin/destination too
        if (! empty($data['route_id'])) {
            $route               = Route::findOrFail($data['route_id']);
            $data['origin']      = $route->origin;
            $data['destination'] = $route->destination;
        }

        $trip->update($data);

        return $trip->fresh(['driver', 'vehicle', 'route', 'dispatcher']);
    }

    /**
     * Start a trip → driver becomes on_trip, record actual start time and GPS.
     */
    public function startTrip(Trip $trip, array $data = []): Trip
    {
        if (! $trip->canBeStarted()) {
            throw new \Exception('This trip cannot be started. Status must be scheduled.');
        }

        return DB::transaction(function () use ($trip, $data) {
            $trip->update([
                'status'          => 'in_progress',
                'actual_start'    => now(),
                'start_latitude'  => $data['latitude']  ?? null,
                'start_longitude' => $data['longitude'] ?? null,
            ]);

            // Driver is now on an active trip
            $trip->driver->update(['status' => 'on_trip']);

            return $trip->fresh(['driver', 'vehicle', 'route', 'dispatcher']);
        });
    }

    /**
     * Complete a trip → driver becomes available, record end time and GPS.
     */
    public function completeTrip(Trip $trip, array $data = []): Trip
    {
        if (! $trip->canBeCompleted()) {
            throw new \Exception('This trip cannot be completed. It must be in progress first.');
        }

        return DB::transaction(function () use ($trip, $data) {
            $trip->update([
                'status'           => 'completed',
                'actual_end'       => now(),
                'end_latitude'     => $data['latitude']       ?? null,
                'end_longitude'    => $data['longitude']      ?? null,
                'total_distance_km'=> $data['distance_km']    ?? null,
                'passengers_count' => $data['passengers_count'] ?? $trip->passengers_count,
            ]);

            // Free the driver back up
            $trip->driver->update(['status' => 'available']);

            return $trip->fresh(['driver', 'vehicle', 'route', 'dispatcher']);
        });
    }

    /**
     * Cancel a trip with an optional reason.
     */
    public function cancelTrip(Trip $trip, ?string $reason = null): Trip
    {
        if (! $trip->canBeCancelled()) {
            throw new \Exception('This trip cannot be cancelled. It is already completed or cancelled.');
        }

        return DB::transaction(function () use ($trip, $reason) {
            $wasInProgress = $trip->isInProgress();

            $trip->update([
                'status'              => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            // Only free the driver if they were actively on this trip
            if ($wasInProgress) {
                $trip->driver->update(['status' => 'available']);
            }

            return $trip->fresh(['driver', 'vehicle', 'route', 'dispatcher']);
        });
    }

    /**
     * Return a paginated, filterable list of trips.
     */
    public function listTrips(array $filters = [])
    {
        $query = Trip::with(['driver.user', 'vehicle', 'route', 'dispatcher'])
                     ->latest('scheduled_start');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('scheduled_start', $filters['date']);
        }

        if (! empty($filters['trip_code'])) {
            $query->where('trip_code', 'like', '%' . $filters['trip_code'] . '%');
        }

        return $query->paginate(15);
    }

    /**
     * Get a single trip with all relationships loaded.
     */
    public function getTripDetails(Trip $trip): Trip
    {
        return $trip->load(['driver.user', 'vehicle', 'route', 'dispatcher', 'gpsPoints']);
    }

    // =========================================================
    // PRIVATE GUARD METHODS
    // =========================================================

    private function ensureDriverIsAvailable(int $driverId): void
    {
        $driver = Driver::findOrFail($driverId);

        if ($driver->status !== 'available') {
            throw new \Exception(
                "Driver is not available. Current status: {$driver->status}."
            );
        }

        $hasActiveTrip = Trip::where('driver_id', $driverId)
                             ->whereIn('status', ['scheduled', 'in_progress'])
                             ->exists();

        if ($hasActiveTrip) {
            throw new \Exception('This driver already has an active or scheduled trip.');
        }
    }

    private function ensureVehicleIsAvailable(int $vehicleId): void
    {
        $hasActiveTrip = Trip::where('vehicle_id', $vehicleId)
                             ->whereIn('status', ['scheduled', 'in_progress'])
                             ->exists();

        if ($hasActiveTrip) {
            throw new \Exception('This vehicle is already assigned to another active trip.');
        }
    }
}