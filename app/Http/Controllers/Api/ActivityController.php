<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;

class ActivityController extends Controller
{
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
            'activity_time' => 'sometimes|date_format:H:i',
            'status' => 'in:SCHEDULED,DONE,CANCELLED',
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
