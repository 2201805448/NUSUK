<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Accommodation;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    // List all trips
    public function index()
    {
        $trips = Trip::with(['accommodations', 'transports.route', 'transports.driver', 'transports.routeFrom', 'transports.routeTo', 'activities'])->get();
        return response()->json($trips);
    }

    // Create a new trip
    public function store(Request $request)
    {
        $request->validate([
            'trip_name' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'in:PLANNED,ONGOING,COMPLETED,CANCELLED',
            'capacity' => 'nullable|integer|min:1',
            'flight_number' => 'nullable|string|max:50',
            'airline' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:200',
        ]);

        $trip = Trip::create($request->all());

        return response()->json([
            'message' => 'تم إنشاء الرحلة العامة بنجاح',
            'trip' => $trip
        ], 201);
    }
    // Get specific trip with its hotels
    public function show($id)
    {
        $trip = Trip::with(['accommodations', 'transports.route', 'transports.driver', 'transports.routeFrom', 'transports.routeTo', 'activities'])->findOrFail($id);
        return response()->json($trip);
    }

    // Update existing trip
    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $request->validate([
            'trip_name' => 'sometimes|string|max:150',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'in:PLANNED,ONGOING,COMPLETED,CANCELLED',
            'capacity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'flight_number' => 'nullable|string|max:50',
            'airline' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:200',
        ]);

        $trip->update($request->all());

        return response()->json([
            'message' => 'Trip updated successfully',
            'trip' => $trip
        ]);
    }

    // Cancel a trip
    public function cancel($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->update(['status' => 'CANCELLED']);

        return response()->json([
            'message' => 'Trip cancelled successfully',
            'trip' => $trip
        ]);
    }

    /**
     * Add hotel data to a trip.
     * Links an existing hotel (by accommodation_id) to a trip.
     */
    public function addHotel(Request $request)
    {
        // Get trip_id from the request body
        $tripId = $request->trip_id;
        $trip = Trip::findOrFail($tripId);

        $validated = $request->validate([
            'accommodation_id' => 'required|exists:accommodations,accommodation_id',
        ]);

        // Check if the hotel is already linked
        $existingLink = $trip->accommodations()
            ->where('trip_accommodations.accommodation_id', $validated['accommodation_id'])
            ->exists();

        if (!$existingLink) {
            // Attach new hotel
            $trip->accommodations()->attach($validated['accommodation_id']);
        }

        // Reload the trip with accommodations
        $trip->load('accommodations');

        // Get the linked accommodation
        $savedAccommodation = $trip->accommodations
            ->where('accommodation_id', $validated['accommodation_id'])
            ->first();

        return response()->json([
            'message' => 'تم ربط الفندق بنجاح',
            'accommodation' => [
                'accommodation_id' => $savedAccommodation->accommodation_id,
                'hotel_name' => $savedAccommodation->hotel_name ?? $savedAccommodation->name ?? null,
                'city' => $savedAccommodation->city ?? null,
                'address' => $savedAccommodation->address ?? null,
                'phone' => $savedAccommodation->phone ?? null,
            ],
            'trip' => $trip
        ]);
    }

    /**
     * Add activity (visit) to a trip
     */
    public function addActivity(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $request->validate([
            'activity_type' => 'required|string|max:100',
            'location' => 'required|string|max:150',
            'activity_date' => 'required|date',
            'activity_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:activity_time',
            'status' => 'in:SCHEDULED,DONE,CANCELLED',
        ]);

        $activity = $trip->activities()->create([
            'activity_type' => $request->activity_type,
            'location' => $request->location,
            'activity_date' => $request->activity_date,
            'activity_time' => $request->activity_time,
            'end_time' => $request->end_time,
            'status' => $request->status ?? 'SCHEDULED',
        ]);

        return response()->json([
            'message' => 'Activity added to trip successfully',
            'activity' => $activity
        ]);
    }


    /**
     * Add transport stage to a trip.
     * This acts as a stage definition for the trip.
     */
    public function addTransport(Request $request, $id)
    {
        // Wrapper to reuse Transport logic or create one directly
        $trip = Trip::findOrFail($id);

        $request->validate([
            'transport_type' => 'required|string|max:50',
            'route_id' => 'nullable|exists:transport_routes,id',
            'driver_id' => 'nullable|exists:drivers,driver_id',
            'route_from' => 'nullable|integer|exists:transport_routes,id',
            'route_to' => 'nullable|integer|exists:transport_routes,id',
            'departure_time' => 'required|date',
            'arrival_time' => 'nullable|date|after:departure_time',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'transport_type' => $request->transport_type,
            'driver_id' => $request->driver_id,
            'route_id' => $request->route_id,
            'route_from' => $request->route_from,
            'route_to' => $request->route_to,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'notes' => $request->notes,
        ];

        // Auto-fill from route if linked
        if ($request->filled('route_id')) {
            $route = \App\Models\TransportRoute::find($request->route_id);
            if ($route) {
                // Auto-calculate arrival_time if not provided
                if (!$request->filled('arrival_time') && $request->filled('departure_time') && $route->estimated_duration_mins) {
                    $departureTime = \Carbon\Carbon::parse($request->departure_time);
                    $data['arrival_time'] = $departureTime->addMinutes($route->estimated_duration_mins);
                }
            }
        }

        $transport = $trip->transports()->create($data);

        // Load relationships for response
        $transport->load(['route', 'driver']);

        return response()->json([
            'message' => 'Transport stage added to trip successfully',
            'transport' => $transport
        ]);
    }

    /**
     * Get hotel reviews for the trip.
     * Returns anonymous reviews for hotels linked to this trip.
     */
    public function getHotelReviews($id)
    {
        $trip = Trip::with('accommodations')->findOrFail($id);
        $reviews = [];

        foreach ($trip->accommodations as $hotel) {
            $hotelReviews = \App\Models\Evaluation::where('type', 'HOTEL')
                ->where('target_id', $hotel->accommodation_id)
                ->where('internal_only', 0)
                ->select('score', 'concern_text', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($hotelReviews->count() > 0) {
                $reviews[] = [
                    'hotel_name' => $hotel->hotel_name,
                    'city' => $hotel->city,
                    'reviews' => $hotelReviews
                ];
            }
        }

        return response()->json($reviews);
    }
}
