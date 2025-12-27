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
        $trips = Trip::with('package')->get();
        return response()->json($trips);
    }

    // Create a new trip
    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,package_id',
            'trip_name' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'in:PLANNED,ONGOING,COMPLETED,CANCELLED',
            'capacity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $trip = Trip::create($request->all());

        return response()->json([
            'message' => 'Trip created successfully',
            'trip' => $trip
        ], 201);
    }

    // Get specific trip with its hotels
    public function show($id)
    {
        $trip = Trip::with(['package', 'accommodations'])->findOrFail($id);
        return response()->json($trip);
    }

    /**
     * Add hotel data to a trip.
     * Can link an existing hotel (by accommodation_id) OR create a new one.
     */
    public function addHotel(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $request->validate([
            // If linking existing
            'accommodation_id' => 'nullable|exists:accommodations,accommodation_id',

            // If creating new
            'hotel_name' => 'required_without:accommodation_id|string|max:150',
            'city' => 'required_without:accommodation_id|string|max:100',
            'room_type' => 'required_without:accommodation_id|string|max:50',
            'capacity' => 'required_without:accommodation_id|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $accommodation = null;

        DB::transaction(function () use ($request, $trip, &$accommodation) {
            if ($request->has('accommodation_id') && $request->accommodation_id) {
                $accommodation = Accommodation::findOrFail($request->accommodation_id);
            } else {
                // Create new accommodation
                $accommodation = Accommodation::create($request->only([
                    'hotel_name',
                    'city',
                    'room_type',
                    'capacity',
                    'notes'
                ]));
            }

            // Sync without detaching existing (attach)
            // check if already attached
            if (!$trip->accommodations()->where('trip_accommodations.accommodation_id', $accommodation->accommodation_id)->exists()) {
                $trip->accommodations()->attach($accommodation->accommodation_id);
            }
        });

        return response()->json([
            'message' => 'Hotel added to trip successfully',
            'trip' => $trip->load('accommodations'),
            'accommodation' => $accommodation
        ]);
    }

    /**
     * Remove hotel from trip
     */
    public function removeHotel($trip_id, $accommodation_id)
    {
        $trip = Trip::findOrFail($trip_id);
        $trip->accommodations()->detach($accommodation_id);

        return response()->json([
            'message' => 'Hotel removed from trip successfully',
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
            'status' => 'in:SCHEDULED,DONE,CANCELLED',
        ]);

        $activity = $trip->activities()->create([
            'activity_type' => $request->activity_type,
            'location' => $request->location,
            'activity_date' => $request->activity_date,
            'activity_time' => $request->activity_time,
            'status' => $request->status ?? 'SCHEDULED',
        ]);

        return response()->json([
            'message' => 'Activity added to trip successfully',
            'activity' => $activity
        ]);
    }
}
