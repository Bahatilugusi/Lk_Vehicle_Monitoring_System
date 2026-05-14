<?php

// app/Services/DriverService.php

namespace App\Services;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DriverService
{
    /**
     * Paginated list of all drivers.
     * Supports ?status=available and ?search=john filters.
     */
    public function getAllDrivers(array $filters = []): LengthAwarePaginator
    {
        $query = Driver::with(['user', 'currentVehicleAssignment.vehicle']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];

            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn($uq) =>
                    $uq->where('name', 'like', "%{$term}%")
                )
                ->orWhere('license_number', 'like', "%{$term}%");
            });
        }

        return $query->latest()->paginate(15);
    }

    /**
     * Register a new driver + their user account.
     *
     * Uses a DB transaction — both records are created together,
     * or neither is saved. This prevents orphaned user accounts
     * with no driver profile, or driver profiles with no login.
     */
    public function createDriver(array $data): Driver
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'driver',
            ]);

            $driver = Driver::create([
                'user_id'           => $user->id,
                'license_number'    => $data['license_number'],
                'license_class'     => $data['license_class'],
                'license_expiry'    => $data['license_expiry'],
                'years_experience'  => $data['years_experience'],
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_phone'   => $data['emergency_phone'] ?? null,
                'status'            => 'available',
            ]);

            return $driver->load(['user', 'currentVehicleAssignment.vehicle']);
        });
    }

    /**
     * Update an existing driver's profile.
     *
     * Handles both the User table (name, email) and
     * the Driver table (credentials, emergency contact) in one call.
     */
    public function updateDriver(Driver $driver, array $data): Driver
    {
        // Split user fields from driver fields
        $userFields = array_filter([
            'name'  => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ]);

        if (! empty($userFields)) {
            $driver->user->update($userFields);
        }

        // Remove user-only fields so they don't get written to the drivers table
        $driverFields = collect($data)
                        ->except(['name', 'email', 'password'])
                        ->toArray();

        $driver->update($driverFields);

        return $driver->load(['user', 'currentVehicleAssignment.vehicle']);
    }

    /**
     * Assign a vehicle to a driver.
     *
     * Business rules enforced:
     *  1. Suspended drivers cannot receive assignments
     *  2. Driver cannot be assigned if already on a trip
     *  3. Driver can only hold ONE active assignment at a time
     *  4. A vehicle can only be assigned to ONE driver at a time
     *
     * When assignment happens:
     *  - A new VehicleDriverAssignment row is created (released_at = null)
     *  - Driver status is set to 'available' (they have a vehicle now)
     *
     * @throws \Exception  When any business rule is violated
     */
    public function assignVehicle(Driver $driver, int $vehicleId, int $assignedBy): VehicleDriverAssignment
    {
        // Rule 1: Suspended drivers cannot be assigned
        if ($driver->status === 'suspended') {
            throw new \Exception('A suspended driver cannot be assigned a vehicle.');
        }

        // Rule 2: Cannot reassign a driver who is currently driving
        if ($driver->isOnTrip()) {
            throw new \Exception('Cannot assign a vehicle to a driver who is currently on a trip.');
        }

        // Rule 3: Driver must not already have an active assignment
        $driverAlreadyAssigned = VehicleDriverAssignment::where('driver_id', $driver->id)
                                                         ->whereNull('released_at')
                                                         ->exists();

        if ($driverAlreadyAssigned) {
            throw new \Exception(
                'This driver already has an active vehicle assignment. ' .
                'Release the current vehicle before assigning a new one.'
            );
        }

        // Rule 4: Vehicle must not be assigned to another driver
        $vehicleAlreadyTaken = VehicleDriverAssignment::where('vehicle_id', $vehicleId)
                                                       ->whereNull('released_at')
                                                       ->exists();

        if ($vehicleAlreadyTaken) {
            $vehicle = Vehicle::find($vehicleId);
            throw new \Exception(
                "Vehicle [{$vehicle->plate_number}] is already assigned to another driver."
            );
        }

        return DB::transaction(function () use ($driver, $vehicleId, $assignedBy) {

            // Create the assignment record
            $assignment = VehicleDriverAssignment::create([
                'driver_id'   => $driver->id,
                'vehicle_id'  => $vehicleId,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
                'released_at' => null,
            ]);

            // Update driver status to available (they now have a vehicle)
            $driver->update(['status' => 'available']);

            return $assignment->load(['driver.user', 'vehicle', 'assignedBy']);
        });
    }

    /**
     * Release a driver from their currently assigned vehicle.
     *
     * This sets released_at on the assignment record — it does NOT delete it.
     * The history is preserved. The driver goes back to off_duty.
     *
     * @throws \Exception  When driver has no active assignment or is on a trip
     */
    public function releaseVehicle(Driver $driver, ?string $reason = null): Driver
    {
        // Find the current active assignment
        $activeAssignment = VehicleDriverAssignment::where('driver_id', $driver->id)
                                                    ->whereNull('released_at')
                                                    ->first();

        if (! $activeAssignment) {
            throw new \Exception('This driver has no active vehicle assignment to release.');
        }

        if ($driver->isOnTrip()) {
            throw new \Exception(
                'Cannot release a vehicle from a driver who is currently on a trip.'
            );
        }

        DB::transaction(function () use ($driver, $activeAssignment, $reason) {

            // Close the assignment — stamp the release time
            $activeAssignment->update([
                'released_at'    => now(),
                'release_reason' => $reason,
            ]);

            // Driver has no vehicle now — back to off_duty
            $driver->update(['status' => 'off_duty']);
        });

        return $driver->load(['user', 'currentVehicleAssignment.vehicle']);
    }

    /**
     * Change a driver's status manually.
     *
     * 'on_trip' is off-limits here — the Trip system manages that automatically.
     * 'available' requires the driver to have an active vehicle assignment.
     *
     * @throws \Exception  On invalid transitions
     */
    public function updateStatus(Driver $driver, string $status): Driver
    {
        if ($status === 'on_trip') {
            throw new \Exception(
                "The 'on_trip' status is set automatically when a trip starts. " .
                "Use the Trip API to manage active trips."
            );
        }

        if ($status === 'available') {
            $hasVehicle = VehicleDriverAssignment::where('driver_id', $driver->id)
                                                  ->whereNull('released_at')
                                                  ->exists();

            if (! $hasVehicle) {
                throw new \Exception(
                    "Driver cannot be set to 'available' without an active vehicle assignment."
                );
            }
        }

        $driver->update(['status' => $status]);

        return $driver->load(['user', 'currentVehicleAssignment.vehicle']);
    }

    /**
     * Return all drivers ready for immediate dispatch.
     * Status must be 'available' and license must not be expired.
     */
    public function getAvailableDrivers(): Collection
    {
        return Driver::with(['user', 'currentVehicleAssignment.vehicle'])
                     ->where('status', 'available')
                     ->where('license_expiry', '>', now())
                     ->get();
    }

    /**
     * Fetch the full vehicle assignment history for one driver.
     * Ordered by most recent assignment first.
     */
    public function getAssignmentHistory(Driver $driver): Collection
    {
        return VehicleDriverAssignment::with(['vehicle', 'assignedBy'])
                                       ->where('driver_id', $driver->id)
                                       ->latest('assigned_at')
                                       ->get();
    }

    /**
     * Soft-delete a driver.
     * Their trips, assignments, and GPS history are all preserved.
     *
     * @throws \Exception  If the driver is currently on a trip
     */
    public function deleteDriver(Driver $driver): void
    {
        if ($driver->isOnTrip()) {
            throw new \Exception(
                'Cannot remove a driver who is currently on an active trip.'
            );
        }

        $driver->delete();
    }
}