<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RoomAssignment;
use Illuminate\Validation\Rule;

class RoomAssignmentController extends Controller
{
    /**
     * Store a newly created room assignment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pilgrim_id' => 'required|exists:pilgrims,pilgrim_id',
            'accommodation_id' => 'required|exists:accommodations,accommodation_id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'status' => 'in:CONFIRMED,PENDING,FINISHED',
        ]);

        // Optional: Check if room capacity is exceeded?
        // Optional: Check if pilgrim is already assigned for dates?

        $assignment = RoomAssignment::create($request->all());

        return response()->json([
            'message' => 'Room assignment created successfully',
            'assignment' => $assignment
        ], 201);
    }
    /**
     * Update the specified room assignment.
     */
    public function update(Request $request, $id)
    {
        $assignment = RoomAssignment::findOrFail($id);

        $request->validate([
            'accommodation_id' => 'sometimes|exists:accommodations,accommodation_id',
            'room_id' => 'sometimes|exists:rooms,id',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after:check_in',
            'status' => 'sometimes|in:CONFIRMED,PENDING,FINISHED',
        ]);

        // If updating room or hotel, we might need validations (capacity, consistency)
        // For now, allow direct update.

        $assignment->update($request->all());

        return response()->json([
            'message' => 'Room assignment updated successfully',
            'assignment' => $assignment
        ]);
    }
}
