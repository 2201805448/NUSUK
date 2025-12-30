<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupervisorNote;
use App\Models\Trip;
use App\Models\Pilgrim;
use Illuminate\Support\Facades\Auth;

class SupervisorNoteController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $pilgrim_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $pilgrim_id)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,trip_id',
            'note_type' => 'required|string|max:50', // e.g., 'BEHAVIORAL', 'ORGANIZATIONAL', 'GENERAL'
            'note_text' => 'required|string',
        ]);

        $pilgrim = Pilgrim::findOrFail($pilgrim_id);

        // Optional: Check if supervisor is assigned to this trip or group?
        // implementation_plan didn't specify strict permission check other than role:SUPERVISOR
        // We'll rely on the Supervisor ID from Auth.

        $note = SupervisorNote::create([
            'pilgrim_id' => $pilgrim_id,
            'supervisor_id' => Auth::id(), // authenticated user
            'trip_id' => $request->trip_id,
            'group_id' => $request->group_id, // Optional, can be null
            'note_type' => $request->note_type,
            'note_text' => $request->note_text,
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'note' => $note
        ], 201);
    }
}
