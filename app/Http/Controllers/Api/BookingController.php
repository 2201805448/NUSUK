<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    // Execute a booking (Create)
    public function store(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,trip_id',
            'pay_method' => 'nullable|string|max:50',
            'request_notes' => 'nullable|string',
        ]);

        $trip = Trip::with('package')->findOrFail($request->trip_id);

        // Ensure trip is bookable (e.g. not cancelled, start date in future?)
        // For now, just check if it's not CANCELLED
        if ($trip->status === 'CANCELLED') {
            return response()->json(['message' => 'Cannot book a cancelled trip.'], 400);
        }

        // Generate Booking Reference
        $bookingRef = 'BK-' . strtoupper(Str::random(10));

        // Calculate Price (Default to Package Price for single person for now)
        $totalPrice = $trip->package ? $trip->package->price : 0;

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'package_id' => $trip->package_id,
            'trip_id' => $trip->trip_id,
            'booking_ref' => $bookingRef,
            'booking_date' => now(),
            'total_price' => $totalPrice,
            'pay_method' => $request->pay_method,
            'status' => 'PENDING',
            'request_notes' => $request->request_notes,
        ]);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking
        ], 201);
    }

    // Request Modification (Companion details, Duration, etc.)
    public function requestModification(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'request_type' => 'required|string|max:50', // e.g. 'CHANGE_COMPANIONS', 'CHANGE_DURATION'
            'request_data' => 'required|array',
        ]);

        $modification = \App\Models\BookingModification::create([
            'booking_id' => $booking->booking_id,
            'request_type' => $request->request_type,
            'request_data' => $request->request_data,
            'status' => 'PENDING',
        ]);

        return response()->json([
            'message' => 'Modification request submitted successfully',
            'modification' => $modification
        ], 201);
    }

    // Request Cancellation
    public function requestCancellation(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        // Optional: Check if already requested?
        $existing = \App\Models\BookingModification::where('booking_id', $booking->booking_id)
            ->where('request_type', 'CANCELLATION')
            ->where('status', 'PENDING')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Cancellation request already pending.'], 400);
        }

        $modification = \App\Models\BookingModification::create([
            'booking_id' => $booking->booking_id,
            'request_type' => 'CANCELLATION',
            'request_data' => ['reason' => $request->reason ?? 'No reason provided'],
            'status' => 'PENDING',
        ]);

        return response()->json([
            'message' => 'Cancellation request submitted successfully',
            'modification' => $modification
        ], 201);
    }
}
