<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;

class ProfileController extends Controller
{
    /**
     * Get the authenticated User's profile.
     * Includes basic info, pilgrim details (if any), and recent bookings (services).
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // 1. Load Pilgrim Details (if exists)
        $user->load('pilgrim');

        // 2. Get Recent Bookings (Use of Services)
        // Assuming 'bookings' table relates to user_id
        $recentBookings = Booking::where('user_id', $user->user_id)
            ->with(['package', 'trip']) // Eager load related service info
            ->orderBy('booking_date', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'user' => $user,
            'services_history' => $recentBookings
        ]);
    }
    /**
     * Update the authenticated User's profile.
     * Allowed fields: full_name, email, phone_number.
     * Pilgrim fields: passport_number, gender, nationality, emergency_call, etc.
     * Blocked fields: role, account_status.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            // User fields
            'full_name' => 'sometimes|string|max:150',
            'email' => 'sometimes|string|email|max:150|unique:users,email,' . $user->user_id . ',user_id',
            'phone_number' => 'sometimes|string|max:30',

            // Pilgrim fields
            'passport_name' => 'nullable|string|max:150',
            'passport_number' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'gender' => 'nullable|string|in:MALE,FEMALE', // Key fix: Uppercase MALE/FEMALE
            'date_of_birth' => 'nullable|date',
            'emergency_call' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($user, $validated) {
            // 1. Update User Table
            $user->update([
                'full_name' => $validated['full_name'] ?? $user->full_name,
                'email' => $validated['email'] ?? $user->email,
                'phone_number' => $validated['phone_number'] ?? $user->phone_number,
            ]);

            // 2. Update or Create Pilgrim Record
            // Extract pilgrim specific fields ensuring we only take what was validated and present
            $pilgrimFields = [
                'passport_name',
                'passport_number',
                'nationality',
                'gender',
                'date_of_birth',
                'emergency_call'
            ];

            $pilgrimData = array_intersect_key($validated, array_flip($pilgrimFields));

            if (!empty($pilgrimData)) {
                $user->pilgrim()->updateOrCreate(
                    ['user_id' => $user->user_id], // Search attributes
                    $pilgrimData                   // Values to update
                );
            }
        });

        // Reload pilgrim relationship to return complete object
        $user->load('pilgrim');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}
