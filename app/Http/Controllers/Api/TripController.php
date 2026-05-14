<?php

namespace App\Http\Controllers\Api;

use App\Models\Trip;
use App\Services\TripService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
    public function __construct(protected TripService $tripService) {}

    // =========================================================
    // LIST TRIPS
    // GET /api/trips
    // =========================================================
    public function index(Request $request): JsonResponse
    {
        $trips = $this->tripService->listTrips(
            $request->only(['status', 'driver_id', 'vehicle_id', 'route_id', 'date', 'trip_code'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Trips retrieved successfully.',
            'data' => $trips
        ]);
    }

    // =========================================================
    // CREATE TRIP
    // POST /api/trips
    // =========================================================
    public function store(Request $request): JsonResponse
    {
        try {
            $trip = Trip::create([
                'trip_code'        => Trip::generateTripCode(),
                'driver_id'        => $request->driver_id,
                'vehicle_id'       => $request->vehicle_id,
                'route_id'         => $request->route_id,
                'origin'           => $request->origin,
                'destination'      => $request->destination,
                'scheduled_start'  => $request->scheduled_start,
                'passengers_count' => $request->passengers_count,
                'notes'            => $request->notes,
                'dispatched_by'    => auth()->id(),
                'status'           => 'scheduled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trip created successfully.',
                'data' => $trip
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================
    // SHOW TRIP
    // GET /api/trips/{trip}
    // =========================================================
public function show(Trip $trip): JsonResponse
{
    try {
        $trip = $this->tripService->getTripDetails($trip);

        return response()->json([
            'success' => true,
            'message' => 'Trip details retrieved successfully.',
            'data' => $trip
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    // =========================================================
    // UPDATE TRIP
    // PUT /api/trips/{trip}
    // =========================================================
    public function update(Request $request, Trip $trip): JsonResponse
    {
        try {
            $updated = $this->tripService->updateTrip($trip, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Trip updated successfully.',
                'data' => $updated
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // =========================================================
    // DELETE TRIP
    // DELETE /api/trips/{trip}
    // =========================================================
    public function destroy(Trip $trip): JsonResponse
    {
        try {
            if (! $trip->isScheduled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only scheduled trips can be deleted.'
                ], 422);
            }

            $trip->delete();

            return response()->json([
                'success' => true,
                'message' => 'Trip deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================
    // START TRIP
    // POST /api/trips/{trip}/start
    // =========================================================
    public function start(Request $request, Trip $trip): JsonResponse
    {
        try {
            $trip = $this->tripService->startTrip(
                $trip,
                $request->only(['latitude', 'longitude'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Trip started successfully.',
                'data' => $trip
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // =========================================================
    // COMPLETE TRIP
    // POST /api/trips/{trip}/complete
    // =========================================================
    public function complete(Request $request, Trip $trip): JsonResponse
    {
        try {
            $trip = $this->tripService->completeTrip(
                $trip,
                $request->only(['latitude', 'longitude', 'distance_km', 'passengers_count'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Trip completed successfully.',
                'data' => $trip
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // =========================================================
    // CANCEL TRIP
    // POST /api/trips/{trip}/cancel
    // =========================================================
    public function cancel(Request $request, Trip $trip): JsonResponse
    {
        try {
            $trip = $this->tripService->cancelTrip(
                $trip,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Trip cancelled successfully.',
                'data' => $trip
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}