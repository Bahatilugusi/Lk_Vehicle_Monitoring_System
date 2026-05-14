<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user
     */
   public function login(Request $request)
{
    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password'
        ], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'token' => $token,
        'user' => $user
    ]);
}

    /**
     * Logout user
     */
    public function logout(Request $request)
{
    // Delete current access token
    $request->user()->currentAccessToken()->delete();

    // Return response
    return response()->json([
        'success' => true,
        'message' => 'You have been logged out successfully.'
    ]);
}

    /**
     * Current authenticated user
     */
    public function me(Request $request)
{
    return response()->json([
        'success' => true,
        'message' => 'Profile retrieved successfully.',
        'data' => $request->user()
    ]);
}

    /**
     * Register new user
     */
    public function register(Request $request)
    {
        return response()->json([
            'message' => 'Register endpoint'
        ]);
    }
}