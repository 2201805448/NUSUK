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
     * Blocked fields: role, account_status.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'email' => 'sometimes|string|email|max:150|unique:users,email,' . $user->user_id . ',user_id',
            'phone_number' => 'sometimes|string|max:30',
        ]);

        $user->update($request->only(['full_name', 'email', 'phone_number']));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}
