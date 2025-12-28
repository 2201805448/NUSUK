<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accommodation;
use Illuminate\Validation\Rule;

class AccommodationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accommodations = Accommodation::all();
        return response()->json($accommodations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'hotel_name' => 'required|string|max:150',
            'city' => 'required|string|max:100',
            'room_type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $accommodation = Accommodation::create($request->all());

        return response()->json([
            'message' => 'Accommodation created successfully',
            'accommodation' => $accommodation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $accommodation = Accommodation::findOrFail($id);
        return response()->json($accommodation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);

        $request->validate([
            'hotel_name' => 'sometimes|string|max:150',
            'city' => 'sometimes|string|max:100',
            'room_type' => 'sometimes|string|max:50',
            'capacity' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $accommodation->update($request->all());

        return response()->json([
            'message' => 'Accommodation updated successfully',
            'accommodation' => $accommodation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $accommodation = Accommodation::findOrFail($id);

        // Optional: Check if used in any room assignments or trips before deleting?
        // simple delete for now, DB constraints should handle restrict/cascade

        $accommodation->delete();

        return response()->json([
            'message' => 'Accommodation deleted successfully'
        ]);
    }

    /**
     * Get Housing Data for a specific Trip.
     * Monitoring room distribution and number of individuals.
     */
    public function getHousingData(Request $request, $trip_id)
    {
        $trip = \App\Models\Trip::findOrFail($trip_id);

        // Security check: Admin or Supervisor
        // If Supervisor, ideally valid for their trip. But currently we don't have explicit Trip-Supervisor (only Group-Supervisor).
        // Assuming Supervisor can view any trip they are involved in or just any trip if role is supervisor.
        // For simplicity: Admin or Supervisor allowed.
        if (!auth()->user() || !in_array(auth()->user()->role, ['ADMIN', 'SUPERVISOR'])) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // Load Accommodations -> Rooms -> RoomAssignments -> Pilgrim
        // Note: RoomAssignments link Pilgrim to Accommodation AND Room (now).

        $hotels = $trip->accommodations()->with([
            'rooms.roomAssignments' => function ($q) {
                // Filter active/confirmed assignments if needed?
                $q->whereIn('status', ['CONFIRMED', 'PENDING']);
            },
            'rooms.roomAssignments.pilgrim.user'
        ])->get();

        $data = $hotels->map(function ($hotel) {
            return [
                'accommodation_id' => $hotel->accommodation_id,
                'hotel_name' => $hotel->hotel_name,
                'city' => $hotel->city,
                'rooms' => $hotel->rooms->map(function ($room) {
                    $occupants = $room->roomAssignments->count();
                    return [
                        'room_id' => $room->id,
                        'room_number' => $room->room_number,
                        'floor' => $room->floor,
                        'room_type' => $room->room_type,
                        'status' => $room->status,
                        'current_occupants' => $occupants,
                        'pilgrims' => $room->roomAssignments->map(function ($assign) {
                            return [
                                'pilgrim_id' => $assign->pilgrim_id,
                                'name' => $assign->pilgrim->user->full_name ?? 'Unknown',
                                'status' => $assign->status
                            ];
                        })
                    ];
                })
            ];
        });

        return response()->json([
            'trip_id' => $trip->trip_id,
            'trip_name' => $trip->trip_name,
            'housing' => $data
        ]);
    }
}
