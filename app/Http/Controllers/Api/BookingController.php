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
    /**
     * View all bookings for the authenticated user (Booking History)
     * Returns current and past bookings with status and basic data
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Booking::where('user_id', $user->user_id)
            ->with(['package', 'trip']);

        // Optional: Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by booking date (most recent first)
        $bookings = $query->orderBy('booking_date', 'desc')->get();

        // Categorize bookings
        $currentBookings = $bookings->filter(function ($booking) {
            return in_array($booking->status, ['PENDING', 'CONFIRMED'])
                && ($booking->trip && $booking->trip->end_date >= now());
        });

        $pastBookings = $bookings->filter(function ($booking) {
            return $booking->status === 'CANCELLED'
                || ($booking->trip && $booking->trip->end_date < now());
        });

        $formattedBookings = $bookings->map(function ($booking) {
            return [
                'booking_id' => $booking->booking_id,
                'booking_ref' => $booking->booking_ref,
                'booking_date' => $booking->booking_date,
                'status' => $booking->status,
                'total_price' => $booking->total_price,
                'pay_method' => $booking->pay_method,
                'package' => $booking->package ? [
                    'package_id' => $booking->package->package_id,
                    'package_name' => $booking->package->package_name ?? null,
                    'duration_days' => $booking->package->duration_days ?? null,
                ] : null,
                'trip' => $booking->trip ? [
                    'trip_id' => $booking->trip->trip_id,
                    'trip_name' => $booking->trip->trip_name ?? null,
                    'start_date' => $booking->trip->start_date ?? null,
                    'end_date' => $booking->trip->end_date ?? null,
                    'trip_status' => $booking->trip->trip_status ?? null,
                ] : null,
                'request_notes' => $booking->request_notes,
                'admin_reply' => $booking->admin_reply,
            ];
        });

        return response()->json([
            'message' => 'Booking history retrieved successfully.',
            'total_count' => $bookings->count(),
            'current_count' => $currentBookings->count(),
            'past_count' => $pastBookings->count(),
            'bookings' => $formattedBookings
        ]);
    }

    /**
     * View a specific booking details
     */
    public function show($id)
    {
        $user = Auth::user();

        $booking = Booking::where('user_id', $user->user_id)
            ->with(['package', 'trip', 'payments'])
            ->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'message' => 'Booking details retrieved successfully.',
            'booking' => [
                'booking_id' => $booking->booking_id,
                'booking_ref' => $booking->booking_ref,
                'booking_date' => $booking->booking_date,
                'status' => $booking->status,
                'total_price' => $booking->total_price,
                'pay_method' => $booking->pay_method,
                'request_notes' => $booking->request_notes,
                'admin_reply' => $booking->admin_reply,
                'package' => $booking->package ? [
                    'package_id' => $booking->package->package_id,
                    'package_name' => $booking->package->package_name ?? null,
                    'duration_days' => $booking->package->duration_days ?? null,
                    'price' => $booking->package->price ?? null,
                    'services' => $booking->package->services ?? null,
                ] : null,
                'trip' => $booking->trip ? [
                    'trip_id' => $booking->trip->trip_id,
                    'trip_name' => $booking->trip->trip_name ?? null,
                    'start_date' => $booking->trip->start_date ?? null,
                    'end_date' => $booking->trip->end_date ?? null,
                    'trip_status' => $booking->trip->trip_status ?? null,
                ] : null,
                'payments' => $booking->payments->map(function ($payment) {
                    return [
                        'payment_id' => $payment->payment_id ?? null,
                        'amount' => $payment->amount ?? null,
                        'payment_date' => $payment->payment_date ?? null,
                        'status' => $payment->status ?? null,
                    ];
                }),
            ]
        ]);
    }

    // Execute a booking (Create)
    public function store(Request $request)
    {
        $user = Auth::user();
        \Illuminate\Support\Facades\Log::info("Booking Attempt: User ID {$user->user_id}, Role: {$user->role}");

        // Only pilgrims can create bookings
        $userRole = Str::lower(trim($request->user()->role));
        if ($userRole !== 'pilgrim') {
            \Illuminate\Support\Facades\Log::warning("Booking Access Denied: User Role '{$userRole}' is not 'pilgrim'.");
            return response()->json(['message' => "Access denied. Your role is: {$request->user()->role}"], 403);
        }

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
