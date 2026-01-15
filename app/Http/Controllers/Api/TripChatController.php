<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\TripMessage;
use App\Models\GroupTrip;
use Illuminate\Support\Facades\Auth;

class TripChatController extends Controller
{
    /**
     * Get chat messages for a specific trip.
     */
    public function index($trip_id)
    {
        $trip = Trip::findOrFail($trip_id);

        if (!$this->isAuthorized($trip)) {
            return response()->json(['message' => 'Unauthorized access to this trip chat.'], 403);
        }

        $messages = TripMessage::with(['sender:user_id,full_name,role'])
            ->where('trip_id', $trip_id)
            ->orderBy('created_at', 'asc') // Oldest first
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a new message to the trip chat.
     */
    public function store(Request $request, $trip_id)
    {
        $trip = Trip::findOrFail($trip_id);

        if (!$this->isAuthorized($trip)) {
            return response()->json(['message' => 'Unauthorized access to this trip chat.'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message = TripMessage::create([
            'trip_id' => $trip->trip_id,
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
        ]);

        // Re-load sender for response
        $message->load('sender:user_id,full_name,role');

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }

    /**
     * Check if authenticated user is authorized to access the trip chat.
     * Admin: Yes
     * Supervisor: Only if they supervise a group within this trip.
     */
    private function isAuthorized(Trip $trip)
    {
        $user = Auth::user();
        if (!$user)
            return false;

        // استخدام getAttribute يحل مشكلة الـ Protected Visibility
        $userRole = $user->getAttribute('role');
        $userId = $user->getAttribute('user_id');

        if ($userRole === 'ADMIN') {
            return true;
        }

        if ($userRole === 'SUPERVISOR') {
            return GroupTrip::where('trip_id', $trip->accommodation_id) // أو id حسب قاعدة بياناتك
                ->where('supervisor_id', $userId)
                ->exists();
        }

        return false;
    }
}
