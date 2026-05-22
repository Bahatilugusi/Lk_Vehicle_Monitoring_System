<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\DriverController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TripController;

/*
|--------------------------------------------------------------------------
| API Routes - Real-Time Vehicle Monitoring System
|--------------------------------------------------------------------------
*/

// =========================================================
// PUBLIC ROUTES - No authentication token required
// =========================================================
Route::prefix('auth')->group(function () {

    // POST /api/auth/login
    Route::post('login', [AuthController::class, 'login']);

});

// =========================================================
// PROTECTED ROUTES - Valid Sanctum token required
// =========================================================
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {

        // POST /api/auth/logout
        Route::post('logout', [AuthController::class, 'logout']);

        // GET /api/auth/me
        Route::get('me', [AuthController::class, 'me']);

        // POST /api/auth/register
        Route::post('register', [AuthController::class, 'register']);

    });

    // Fleet, Driver, Trip, GPS routes will be added here next

});
// =========================================================
// PROTECTED ROUTES - Valid Sanctum token required
// =========================================================
Route::middleware('auth:sanctum')->group(function () {

    // ================= AUTH =================
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('register', [AuthController::class, 'register']);
    });

    // ================= DRIVERS =================
    Route::prefix('drivers')->name('drivers.')->group(function () {

        Route::get('available', [DriverController::class, 'available'])->name('available');

        Route::get('/',          [DriverController::class, 'index']);
        Route::post('/',         [DriverController::class, 'store']);
        Route::get('{driver}',   [DriverController::class, 'show']);
        Route::put('{driver}',   [DriverController::class, 'update']);
        Route::delete('{driver}',[DriverController::class, 'destroy']);

        Route::patch('{driver}/status', [DriverController::class, 'updateStatus']);
        Route::post('{driver}/assign-vehicle', [DriverController::class, 'assignVehicle']);
        Route::patch('{driver}/release-vehicle', [DriverController::class, 'releaseVehicle']);
        Route::get('{driver}/assignments', [DriverController::class, 'assignmentHistory']);
    });

    // ================= TRIPS (FIXED) =================
    Route::prefix('trips')->group(function () {

        Route::get('/',    [TripController::class, 'index']);
        Route::post('/',   [TripController::class, 'store']);

        Route::get('{trip}',    [TripController::class, 'show']);
        Route::put('{trip}',    [TripController::class, 'update']);
        Route::delete('{trip}', [TripController::class, 'destroy']);

        Route::post('{trip}/start',    [TripController::class, 'start']);
        Route::post('{trip}/complete', [TripController::class, 'complete']);
        Route::post('{trip}/cancel',   [TripController::class, 'cancel']);
    });

});

