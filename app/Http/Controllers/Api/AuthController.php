<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:users',
            'phone_number' => 'required|string|max:30',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password), // Using hashed cast but safety first or redundant if cast works
            'role' => 'Pilgrim',
            'account_status' => 'ACTIVE',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'nullable|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Update FCM Token if provided because user might be logging in from a new device
        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Normalize role to proper title case for frontend consistency
        $roleMap = [
            'admin' => 'Admin',
            'supervisor' => 'Supervisor',
            'pilgrim' => 'Pilgrim',
            'support' => 'Support',
        ];
        $normalizedRole = $roleMap[strtolower(trim($user->role))] ?? ucfirst(strtolower($user->role));

        // Determine redirect URL based on role
        $redirectUrl = '/dashboard'; // Default redirect
        if (strtoupper($user->role) === 'ADMIN') {
            $redirectUrl = '/admin/dashboard';
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'role' => $normalizedRole,
            'token' => $token,
            'redirect_url' => $redirectUrl,
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // Change Password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed', // expects new_password_confirmation
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password does not match.'
            ], 400);
        }

        $user->forceFill([
            'password' => Hash::make($request->new_password)
        ])->save();

        return response()->json([
            'message' => 'Password changed successfully.'
        ]);
    }
}
