<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;

class ProfileController extends Controller
{
    /**
     * Convert a relative storage path to an absolute URL.
     * Handles both already-absolute URLs and relative paths.
     */
    private function getAbsoluteImageUrl($imagePath)
    {
        if (empty($imagePath)) {
            return null;
        }

        // If it's already an absolute URL, return as-is
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        // Convert relative path to absolute URL
        return url('storage/' . $imagePath);
    }

    /**
     * Get the authenticated User's profile.
     * Includes basic info, pilgrim details (if any), and recent bookings (services).
     * Returns a FLATTENED response where pilgrim fields are merged into the user object.
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

        // 3. Flatten the response - merge pilgrim data into user object
        $userData = $user->toArray();

        // Remove nested pilgrim object and flatten its properties into user
        if (isset($userData['pilgrim']) && is_array($userData['pilgrim'])) {
            $pilgrimData = $userData['pilgrim'];
            unset($userData['pilgrim']);

            // Merge pilgrim fields directly into user data
            $userData['pilgrim_id'] = $pilgrimData['pilgrim_id'] ?? null;
            $userData['passport_name'] = $pilgrimData['passport_name'] ?? null;
            $userData['passport_number'] = $pilgrimData['passport_number'] ?? null;
            $userData['nationality'] = $pilgrimData['nationality'] ?? null;
            $userData['date_of_birth'] = $pilgrimData['date_of_birth'] ?? null;
            $userData['gender'] = $pilgrimData['gender'] ?? null;
            $userData['emergency_call'] = $pilgrimData['emergency_call'] ?? null;
            $userData['notes'] = $pilgrimData['notes'] ?? null;

            // Convert image paths to absolute URLs
            $userData['passport_img'] = $this->getAbsoluteImageUrl($pilgrimData['passport_img'] ?? null);
            $userData['visa_img'] = $this->getAbsoluteImageUrl($pilgrimData['visa_img'] ?? null);
        } else {
            // No pilgrim data - set all pilgrim fields to null
            $userData['pilgrim_id'] = null;
            $userData['passport_name'] = null;
            $userData['passport_number'] = null;
            $userData['nationality'] = null;
            $userData['date_of_birth'] = null;
            $userData['gender'] = null;
            $userData['emergency_call'] = null;
            $userData['notes'] = null;
            $userData['passport_img'] = null;
            $userData['visa_img'] = null;
        }

        return response()->json([
            'user' => $userData,
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
            'passport_img' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'visa_img' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        DB::transaction(function () use ($user, $validated, $request) {
            // 1. Update User Table
            $user->update([
                'full_name' => $validated['full_name'] ?? $user->full_name,
                'email' => $validated['email'] ?? $user->email,
                'phone_number' => $validated['phone_number'] ?? $user->phone_number,
            ]);

            // 2. Update or Create Pilgrim Record
            // Extract pilgrim specific fields
            $pilgrimFields = [
                'passport_name',
                'passport_number',
                'nationality',
                'gender',
                'date_of_birth',
                'emergency_call',
            ];

            $pilgrimData = array_intersect_key($validated, array_flip($pilgrimFields));

            // Handle File Uploads
            if ($request->hasFile('passport_img')) {
                $path = $request->file('passport_img')->store('pilgrims/passports', 'public');
                $pilgrimData['passport_img'] = $path;
            }

            if ($request->hasFile('visa_img')) {
                $path = $request->file('visa_img')->store('pilgrims/visas', 'public');
                $pilgrimData['visa_img'] = $path;
            }

            if (!empty($pilgrimData)) {
                $user->pilgrim()->updateOrCreate(
                    ['user_id' => $user->user_id], // Search attributes
                    $pilgrimData                   // Values to update
                );
            }
        });

        // Reload pilgrim relationship to return complete object
        $user->load('pilgrim');

        // Flatten the response - merge pilgrim data into user object
        $userData = $user->toArray();

        // Remove nested pilgrim object and flatten its properties into user
        if (isset($userData['pilgrim']) && is_array($userData['pilgrim'])) {
            $pilgrimData = $userData['pilgrim'];
            unset($userData['pilgrim']);

            // Merge pilgrim fields directly into user data
            $userData['pilgrim_id'] = $pilgrimData['pilgrim_id'] ?? null;
            $userData['passport_name'] = $pilgrimData['passport_name'] ?? null;
            $userData['passport_number'] = $pilgrimData['passport_number'] ?? null;
            $userData['nationality'] = $pilgrimData['nationality'] ?? null;
            $userData['date_of_birth'] = $pilgrimData['date_of_birth'] ?? null;
            $userData['gender'] = $pilgrimData['gender'] ?? null;
            $userData['emergency_call'] = $pilgrimData['emergency_call'] ?? null;
            $userData['notes'] = $pilgrimData['notes'] ?? null;

            // Convert image paths to absolute URLs
            $userData['passport_img'] = $this->getAbsoluteImageUrl($pilgrimData['passport_img'] ?? null);
            $userData['visa_img'] = $this->getAbsoluteImageUrl($pilgrimData['visa_img'] ?? null);
        } else {
            // No pilgrim data - set all pilgrim fields to null
            $userData['pilgrim_id'] = null;
            $userData['passport_name'] = null;
            $userData['passport_number'] = null;
            $userData['nationality'] = null;
            $userData['date_of_birth'] = null;
            $userData['gender'] = null;
            $userData['emergency_call'] = null;
            $userData['notes'] = null;
            $userData['passport_img'] = null;
            $userData['visa_img'] = null;
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $userData
        ]);
    }
}
