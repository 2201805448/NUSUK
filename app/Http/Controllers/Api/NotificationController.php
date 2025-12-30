<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use App\Models\Trip;
use App\Models\GroupTrip;
use App\Models\Booking;
use App\Models\Pilgrim;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Send a general notification to all users (Admin only).
     */
    public function sendGeneral(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string',
        ]);

        // Optimization: Use chunking or queue for large number of users.
        // For now, doing direct insert for simplicity, but strictly should be queued.
        // We can use a raw Insert for performance if needed.

        $users = User::pluck('user_id'); // Get all user IDs

        $notifications = [];
        $now = now();

        foreach ($users as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'title' => $request->title,
                'message' => $request->message,
                'is_read' => false,
                'created_at' => $now,
            ];
        }

        // Insert in chunks of 500
        foreach (array_chunk($notifications, 500) as $chunk) {
            Notification::insert($chunk);
        }

        return response()->json(['message' => 'General notification sent to ' . count($users) . ' users.']);
    }

    /**
     * Send notification to all pilgrims (users) in a specific trip.
     */
    public function sendTrip(Request $request, $trip_id)
    {
        // Supervisor or Admin

        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string',
        ]);

        $trip = Trip::findOrFail($trip_id);

        // Find users who have a booking for this trip
        // Logic: Booking -> User (The one who booked)
        // Also potentially: Booking -> Attendees -> Pilgrim -> User (if attendees are separate users)
        // For simplicity and safety, let's target the Users who made the Bookings.

        $userIds = Booking::where('trip_id', $trip_id)
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'PENDING']) // Filter status?
            ->pluck('user_id')
            ->unique();

        // Also merge Pilgrim Users if logical? 
        // Let's stick to Booking User as they are the account holder.

        if ($userIds->isEmpty()) {
            return response()->json(['message' => 'No users found for this trip.'], 404);
        }

        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'title' => $request->title,
                'message' => $request->message,
                'is_read' => false,
                'created_at' => $now,
            ];
        }

        Notification::insert($notifications);

        return response()->json(['message' => 'Notification sent to ' . count($userIds) . ' users in the trip.']);
    }

    /**
     * Send notification to all pilgrims (users) in a specific group.
     */
    public function sendGroup(Request $request, $group_id)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string',
        ]);

        $group = GroupTrip::findOrFail($group_id);

        // Group -> GroupMember -> Pilgrim -> User
        // We need users to send notifications to their accounts.

        $userIds = DB::table('group_members')
            ->join('pilgrims', 'group_members.pilgrim_id', '=', 'pilgrims.pilgrim_id')
            ->where('group_members.group_id', $group_id)
            ->pluck('pilgrims.user_id')
            ->unique();

        if ($userIds->isEmpty()) {
            return response()->json(['message' => 'No users found for this group.'], 404);
        }

        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'title' => $request->title,
                'message' => $request->message,
                'is_read' => false,
                'created_at' => $now,
            ];
        }

        Notification::insert($notifications);

        return response()->json(['message' => 'Notification sent to ' . count($userIds) . ' users in the group.']);
    }

    private function authorizeAdmin()
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403, 'Unauthorized. Admins only.');
        }
    }
}
