<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TripUpdate;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class TripUpdateController extends Controller
{
    /**
     * Store a new trip update and notify pilgrims.
     */
    public function store(Request $request, $trip_id)
    {
        // Supervisor or Admin only

        $request->validate([
            'title' => 'required|string|max:150',
            'message' => 'required|string',
        ]);

        $trip = Trip::findOrFail($trip_id);

        // 1. Create Update Record
        $update = TripUpdate::create([
            'trip_id' => $trip_id,
            'title' => $request->title,
            'message' => $request->message,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        // 2. Notify Users (reusing logic from NotificationController or replicating)
        // Replicating for independence.

        $userIds = Booking::where('trip_id', $trip_id)
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'PENDING'])
            ->pluck('user_id')
            ->unique();

        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'title' => 'Trip Update: ' . $request->title,
                'message' => $request->message,
                'is_read' => false,
                'created_at' => $now,
            ];
        }

        if (!empty($notifications)) {
            // Chunking
            foreach (array_chunk($notifications, 500) as $chunk) {
                Notification::insert($chunk);
            }
        }

        return response()->json([
            'message' => 'Trip update posted and notifications sent.',
            'update' => $update
        ], 201);
    }

    /**
     * Get list of updates for a trip.
     */
    public function index($trip_id)
    {
        $updates = TripUpdate::where('trip_id', $trip_id)
            ->orderBy('created_at', 'desc')
            ->with(['creator:user_id,full_name']) // Eager load creator name
            ->get();

        return response()->json($updates);
    }
}
