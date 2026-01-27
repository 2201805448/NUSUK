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

        // Send FCM Notifications
        $this->sendFcmToUsers($users, $request->title, $request->message);

        return response()->json(['message' => 'General notification sent to ' . count($users) . ' users.']);
    }

    /**
     * Send a notification to a specific user.
     */
    public function sendToUser(Request $request, $user_id)
    {
        // Supervisor or Admin
        
        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string',
        ]);

        $user = User::findOrFail($user_id);

        $notification = [
            'user_id' => $user->user_id,
            'title' => $request->title,
            'message' => $request->message,
            'is_read' => false,
            'created_at' => now(),
        ];

        Notification::insert($notification);

        // Send FCM
        $this->sendFcmToUsers([$user->user_id], $request->title, $request->message);

        return response()->json(['message' => 'Notification sent to user.']);
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

        // Send FCM
        $this->sendFcmToUsers($userIds, $request->title, $request->message);

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

        // Send FCM
        $this->sendFcmToUsers($userIds, $request->title, $request->message);

        return response()->json(['message' => 'Notification sent to ' . count($userIds) . ' users in the group.']);
    }

    /**
     * Helper to send FCM notifications
     */
    private function sendFcmToUsers($userIds, $title, $body)
    {
        try {
            $tokens = User::whereIn('user_id', $userIds)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->all();

            if (empty($tokens)) {
                return;
            }

            $messaging = app('firebase.messaging');

            $message = \Kreait\Firebase\Messaging\CloudMessage::new()
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body));

            // Per documentation, multicast is for sending same message to multiple tokens
            // $messaging->sendMulticast($message, $tokens);
            // Verify library version support. sendMulticast is standard.

            $report = $messaging->sendMulticast($message, $tokens);

            // Log successes/failures if needed
            // Log::info('FCM Sent: ' . $report->successes()->count() . ' Success, ' . $report->failures()->count() . ' Failures');

        } catch (\Throwable $e) {
            // Log error but don't fail request
            // Log::error('FCM Error: ' . $e->getMessage());
        }
    }

    /**
     * Get user's notifications.
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('notification_id', $id)
            ->firstOrFail();

        $notification->is_read = true;
        $notification->save(); // Model doesn't have timestamps, so save() is fine (created_at is manual). 
        // Note: Notification model has $timestamps = false.

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    private function authorizeAdmin()
    {
        if (strtoupper(Auth::user()->role) !== 'ADMIN') {
            abort(403, 'Unauthorized. Admins only.');
        }
    }
}
