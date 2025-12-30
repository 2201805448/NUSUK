<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceTracking;
use App\Models\Pilgrim;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Store a newly created attendance record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $pilgrim_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $pilgrim_id)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,trip_id',
            'activity_id' => 'nullable|exists:activities,activity_id',
            'status_type' => 'required|in:ARRIVAL,DEPARTURE,ABSENT',
            'supervisor_note' => 'nullable|string',
            'timestamp' => 'nullable|date', // Optional, defaults to now
        ]);

        $pilgrim = Pilgrim::findOrFail($pilgrim_id);

        $attendance = AttendanceTracking::create([
            'pilgrim_id' => $pilgrim_id,
            'trip_id' => $request->trip_id,
            'activity_id' => $request->activity_id,
            'status_type' => $request->status_type,
            'timestamp' => $request->timestamp ?? now(),
            'supervisor_id' => Auth::id(),
            'supervisor_note' => $request->supervisor_note,
        ]);

        return response()->json([
            'message' => 'Attendance recorded successfully',
            'attendance' => $attendance
        ], 201);
    }

    /**
     * Display a listing of attendance records for a specific trip.
     *
     * @param  int  $trip_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTripReports($trip_id)
    {
        $reports = AttendanceTracking::where('trip_id', $trip_id)
            ->with(['pilgrim', 'activity']) // Eager load relationships
            ->orderBy('timestamp', 'desc')
            ->get();

        return response()->json($reports);
    }
}
