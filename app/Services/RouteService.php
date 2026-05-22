<?php

namespace App\Services;

use App\Models\Route;
use Illuminate\Support\Facades\Auth;

class RouteService
{
    /**
     * Create a new route.
     * The authenticated user is recorded as the creator.
     */
    public function createRoute(array $data): Route
    {
        return Route::create([
            'name'           => $data['name'],
            'origin'         => $data['origin'],
            'destination'    => $data['destination'],
            'distance_km'    => $data['distance_km'] ?? null,
            'estimated_time' => $data['estimated_time'] ?? null,
            'description'    => $data['description'] ?? null,
            'status'         => $data['status'] ?? 'active',
            'created_by'     => Auth::id(),
        ]);
    }

    /**
     * Update an existing route's details.
     */
    public function updateRoute(Route $route, array $data): Route
    {
        $route->update($data);

        return $route->fresh(['creator']);
    }

    /**
     * Deactivate a route instead of deleting it
     * if it has trips attached.
     * Hard soft-delete only if no trips exist.
     */
    public function deleteRoute(Route $route): string
    {
        $hasTripHistory = $route->trips()->exists();

        if ($hasTripHistory) {
            // Route has trips — we cannot remove it from history
            // so we deactivate it instead
            $route->update(['status' => 'inactive']);

            return 'deactivated';
        }

        // No trips attached — safe to soft delete
        $route->delete();

        return 'deleted';
    }

    /**
     * Get a paginated, filterable list of routes.
     */
    public function listRoutes(array $filters = [])
    {
        $query = Route::with(['creator'])
                      ->withCount('trips')
                      ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('origin', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('destination', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate(15);
    }

    /**
     * Get full details of a single route including
     * recent trips and creator.
     */
    public function getRouteDetails(Route $route): Route
    {
        return $route->load(['creator', 'trips' => function ($query) {
            $query->latest()->limit(10);
        }]);
    }

    /**
     * Return only active routes — used by dispatcher
     * when creating a trip (dropdown list).
     */
    public function getActiveRoutes(): \Illuminate\Database\Eloquent\Collection
    {
        return Route::where('status', 'active')
                    ->orderBy('name')
                    ->get(['id', 'name', 'origin', 'destination',
                           'distance_km', 'estimated_time']);
    }
}