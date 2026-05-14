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

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('drivers')->name('drivers.')->group(function () {

        //  Specific named routes MUST come before {driver} wildcard
        Route::get('available', [DriverController::class, 'available'])->name('available');

        // Core CRUD
        Route::get('/',          [DriverController::class, 'index'])->name('index');
        Route::post('/',         [DriverController::class, 'store'])->name('store');
        Route::get('{driver}',   [DriverController::class, 'show'])->name('show');
        Route::put('{driver}',   [DriverController::class, 'update'])->name('update');
        Route::delete('{driver}',[DriverController::class, 'destroy'])->name('destroy');

        // Driver operations
        Route::patch('{driver}/status',          [DriverController::class, 'updateStatus'])->name('status');
        Route::post('{driver}/assign-vehicle',   [DriverController::class, 'assignVehicle'])->name('assign-vehicle');
        Route::patch('{driver}/release-vehicle', [DriverController::class, 'releaseVehicle'])->name('release-vehicle');
        Route::get('{driver}/assignments',       [DriverController::class, 'assignmentHistory'])->name('assignments');
    });

      // Trip & Dispatch Management
Route::prefix('trips')->group(function () {
    Route::get('/',    [TripController::class, 'index']);
    Route::post('/',   [TripController::class, 'store']);
    Route::get('/{trip}',    [TripController::class, 'show']);
    Route::put('/{trip}',    [TripController::class, 'update']);
    Route::delete('/{trip}', [TripController::class, 'destroy']);

    // Lifecycle actions
    Route::post('/{trip}/start',    [TripController::class, 'start']);
    Route::post('/{trip}/complete', [TripController::class, 'complete']);
    Route::post('/{trip}/cancel',   [TripController::class, 'cancel']);
});
  

});

