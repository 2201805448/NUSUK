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
        $trips = Trip::with(['accommodations', 'transports', 'activities'])->get();
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
        $trip = Trip::with(['accommodations', 'transports', 'activities'])->findOrFail($id);
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
     * Can link an existing hotel (by accommodation_id) OR create a new one.
     */
    public function addHotel(Request $request) // احذفي متغير $id من هنا
    {
        // استلام الـ ID من البيانات المرسلة
        $tripId = $request->trip_id;
        $trip = Trip::findOrFail($tripId);

        $request->validate([
            'accommodation_id' => 'required|exists:accommodations,accommodation_id',
        ]);

        if (!$trip->accommodations()->where('trip_accommodations.accommodation_id', $request->accommodation_id)->exists()) {
            $trip->accommodations()->attach($request->accommodation_id);
        }

        return response()->json([
            'message' => 'تم ربط الفندق بنجاح',
            'trip' => $trip->load('accommodations')
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
            'route_from' => 'required|string|max:100',
            'route_to' => 'required|string|max:100',
            'departure_time' => 'required|date',
            'arrival_time' => 'nullable|date|after:departure_time',
            'notes' => 'nullable|string',
        ]);

        $transport = $trip->transports()->create([
            'transport_type' => $request->transport_type,
            'route_from' => $request->route_from,
            'route_to' => $request->route_to,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'notes' => $request->notes,
        ]);

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
