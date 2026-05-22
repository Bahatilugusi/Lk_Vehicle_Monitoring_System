<?php

namespace App\Http\Controllers\Api;

use App\Models\Route;
use App\Services\RouteService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RouteController extends Controller
{
    public function __construct(protected RouteService $routeService)
    {
    }

    /**
     * List all routes with optional filters.
     * GET /api/routes?status=active&search=mbeya
     */
    public function index(Request $request): JsonResponse
    {
        $routes = $this->routeService->listRoutes(
            $request->only(['status', 'search'])
        );

        return $this->successResponse($routes, 'Routes retrieved successfully.');
    }

    /**
     * Get only active routes — for trip creation dropdowns.
     * GET /api/routes/active
     */
    public function active(): JsonResponse
    {
        $routes = $this->routeService->getActiveRoutes();

        return $this->successResponse($routes, 'Active routes retrieved.');
    }

    /**
     * Create a new route.
     * POST /api/routes
     */
    public function store(StoreRouteRequest $request): JsonResponse
    {
        $route = $this->routeService->createRoute($request->validated());

        $route->load('creator');

        return $this->successResponse($route, 'Route created successfully.', 201);
    }

    /**
     * Get a single route with full details.
     * GET /api/routes/{route}
     */
    public function show(Route $route): JsonResponse
    {
        $route = $this->routeService->getRouteDetails($route);

        // Add formatted duration to the response
        $route->formatted_duration = $route->formattedDuration();

        return $this->successResponse($route, 'Route details retrieved.');
    }

    /**
     * Update a route's details.
     * PUT /api/routes/{route}
     */
    public function update(UpdateRouteRequest $request, Route $route): JsonResponse
    {
        $updated = $this->routeService->updateRoute($route, $request->validated());

        return $this->successResponse($updated, 'Route updated successfully.');
    }

    /**
     * Delete or deactivate a route.
     * DELETE /api/routes/{route}
     */
    public function destroy(Route $route): JsonResponse
    {
        $outcome = $this->routeService->deleteRoute($route);

        $message = $outcome === 'deleted'
            ? 'Route deleted successfully.'
            : 'Route has existing trips and has been deactivated instead of deleted.';

        return $this->successResponse(null, $message);
    }
}