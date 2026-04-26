<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:admin,cashier,staff',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid credentials',
                    'message' => 'The provided credentials are incorrect.'
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => 'Account disabled',
                    'message' => 'Your account has been disabled.'
                ], 403);
            }

            $token = $user->createToken('efiche-api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => null,
                ],
                'expires_in' => 3600
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Login failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Logout failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => null,
                'created_at' => $user->created_at->toISOString(),
                'last_login' => $user->updated_at->toISOString()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('facility');

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'facility_id' => $user->facility_id,
                    'facility' => $user->facility,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->toISOString(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve profile',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();
            $token = $user->createToken('efiche-api-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'message' => 'Token refreshed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Token refresh failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
