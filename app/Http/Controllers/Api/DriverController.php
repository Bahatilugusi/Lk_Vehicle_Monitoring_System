<?php

// app/Http/Controllers/Api/DriverController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Services\DriverService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DriverService $driverService)
    {
    }

    /**
     * GET /api/drivers
     */
    public function index(Request $request): JsonResponse
    {
        $drivers = $this->driverService->getAllDrivers(
            $request->only(['status', 'search'])
        );

        return $this->successResponse(
            DriverResource::collection($drivers)->response()->getData(true),
            'Drivers retrieved successfully.'
        );
    }

    /**
     * POST /api/drivers
     */
    public function store(StoreDriverRequest $request): JsonResponse
    {
        try {
            $driver = $this->driverService->createDriver($request->validated());

            return $this->successResponse(
                new DriverResource($driver),
                'Driver registered successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/drivers/{driver}
     */
    public function show(Driver $driver): JsonResponse
    {
        $driver->load(['user', 'currentVehicleAssignment.vehicle']);

        return $this->successResponse(
            new DriverResource($driver),
            'Driver profile retrieved.'
        );
    }

    /**
     * PUT /api/drivers/{driver}
     */
    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        try {
            $updated = $this->driverService->updateDriver($driver, $request->validated());

            return $this->successResponse(
                new DriverResource($updated),
                'Driver profile updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * PATCH /api/drivers/{driver}/status
     */
    public function updateStatus(Request $request, Driver $driver): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:available,off_duty,suspended'],
        ]);

        try {
            $updated = $this->driverService->updateStatus($driver, $request->status);

            return $this->successResponse(
                new DriverResource($updated),
                "Driver status updated to '{$request->status}'."
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/drivers/{driver}/assign-vehicle
     */
    public function assignVehicle(Request $request, Driver $driver): JsonResponse
    {
        $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
        ]);

        try {
            $assignment = $this->driverService->assignVehicle(
                $driver,
                $request->vehicle_id,
                auth()->id()           // who is making this assignment
            );

            return $this->successResponse(
                new AssignmentResource($assignment),
                'Vehicle assigned to driver successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * PATCH /api/drivers/{driver}/release-vehicle
     */
    public function releaseVehicle(Request $request, Driver $driver): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $updated = $this->driverService->releaseVehicle($driver, $request->reason);

            return $this->successResponse(
                new DriverResource($updated),
                'Vehicle released. Driver is now off duty.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/drivers/{driver}/assignments
     * Full vehicle assignment history for this driver.
     */
    public function assignmentHistory(Driver $driver): JsonResponse
    {
        $history = $this->driverService->getAssignmentHistory($driver);

        return $this->successResponse(
            AssignmentResource::collection($history),
            'Assignment history retrieved.'
        );
    }

    /**
     * GET /api/drivers/available
     */
    public function available(): JsonResponse
    {
        $drivers = $this->driverService->getAvailableDrivers();

        return $this->successResponse(
            DriverResource::collection($drivers),
            'Available drivers retrieved.'
        );
    }

    /**
     * DELETE /api/drivers/{driver}
     */
    public function destroy(Driver $driver): JsonResponse
    {
        try {
            $this->driverService->deleteDriver($driver);

            return $this->successResponse(null, 'Driver removed from the system.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}