<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Accommodation;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms.
     * Optionally filter by accommodation_id
     */
    public function index(Request $request)
    {
        $query = Room::query();

        if ($request->has('accommodation_id')) {
            $query->where('accommodation_id', $request->accommodation_id);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request)
    {
        $request->validate([
            'accommodation_id' => 'required|exists:accommodations,accommodation_id',
            'room_number' => [
                'required',
                'string',
                'max:50',
                // Unique room number per accommodation
                Rule::unique('rooms')->where(function ($query) use ($request) {
                    return $query->where('accommodation_id', $request->accommodation_id);
                }),
            ],
            'floor' => 'nullable|integer',
            'room_type' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'status' => 'in:AVAILABLE,OCCUPIED,MAINTENANCE,CLEANING',
            'notes' => 'nullable|string',
        ]);

        $room = Room::create($request->all());

        return response()->json([
            'message' => 'Room created successfully',
            'room' => $room
        ], 201);
    }

    /**
     * Display the specified room.
     */
    public function show($id)
    {
        $room = Room::with('accommodation')->findOrFail($id);
        return response()->json($room);
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_number' => [
                'sometimes',
                'string',
                'max:50',
                // Unique ignore current
                Rule::unique('rooms')->where(function ($query) use ($room, $request) {
                    return $query->where('accommodation_id', $request->accommodation_id ?? $room->accommodation_id);
                })->ignore($room->id),
            ],
            'floor' => 'nullable|integer',
            'room_type' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'status' => 'in:AVAILABLE,OCCUPIED,MAINTENANCE,CLEANING',
            'notes' => 'nullable|string',
        ]);

        $room->update($request->all());

        return response()->json([
            'message' => 'Room updated successfully',
            'room' => $room
        ]);
    }

    /**
     * Remove the specified room.
     */
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }
}
