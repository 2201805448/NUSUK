<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;

class ActivityController extends Controller
{
    /**
     * عرض كل الأنشطة (اختياري)
     */
    public function index()
    {
        return response()->json(Activity::all());
    }

    /**
     * حفظ نشاط جديد (هادي هي الدالة اللي كانت ناقصة)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,trip_id',
            'activity_type' => 'required|string|max:100',
            'location' => 'required|string|max:150',
            'activity_date' => 'required|date',
            'activity_time' => 'required', // HH:mm format
            'end_time' => 'nullable',
            'status' => 'in:SCHEDULED,IN_PROGRESS,DONE,CANCELLED',
        ]);

        $activity = Activity::create($validated);

        return response()->json([
            'message' => 'Activity created successfully',
            'activity' => $activity
        ], 201);
    }

    /**
     * Display the specified activity.
     */
    public function show($id)
    {
        $activity = Activity::findOrFail($id);
        return response()->json($activity);
    }

    /**
     * Update the specified activity in storage.
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $request->validate([
            'activity_type' => 'sometimes|string|max:100',
            'location' => 'sometimes|string|max:150',
            'activity_date' => 'sometimes|date',
            'activity_time' => 'sometimes',
            'status' => 'in:SCHEDULED,IN_PROGRESS,DONE,CANCELLED',
        ]);

        $activity->update($request->all());

        return response()->json([
            'message' => 'Activity updated successfully',
            'activity' => $activity
        ]);
    }

    /**
     * Remove the specified activity from storage.
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully'
        ]);
    }
}