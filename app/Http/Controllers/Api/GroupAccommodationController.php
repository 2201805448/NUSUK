<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupTrip;
use App\Models\Accommodation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupAccommodationController extends Controller
{
    /**
     * List all accommodations linked to a group.
     */
    public function index($group_id)
    {
        $group = GroupTrip::with(['accommodations', 'trip'])
            ->findOrFail($group_id);

        return response()->json([
            'message' => 'Group accommodations retrieved successfully.',
            'group' => [
                'group_id' => $group->group_id,
                'group_code' => $group->group_code,
                'trip' => $group->trip ? [
                    'trip_id' => $group->trip->trip_id,
                    'trip_name' => $group->trip->trip_name,
                ] : null,
            ],
            'accommodations_count' => $group->accommodations->count(),
            'accommodations' => $group->accommodations->map(function ($acc) {
                return [
                    'accommodation_id' => $acc->accommodation_id,
                    'hotel_name' => $acc->hotel_name,
                    'city' => $acc->city,
                    'room_type' => $acc->room_type,
                    'capacity' => $acc->capacity,
                    'assignment' => [
                        'check_in_date' => $acc->pivot->check_in_date,
                        'check_out_date' => $acc->pivot->check_out_date,
                        'notes' => $acc->pivot->notes,
                        'assigned_at' => $acc->pivot->created_at,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Link an accommodation to a group.
     */
    public function link(Request $request, $group_id)
    {
        $group = GroupTrip::with('trip')->findOrFail($group_id);

        $request->validate([
            'accommodation_id' => 'required|exists:accommodations,accommodation_id',
            'check_in_date' => 'nullable|date',
            'check_out_date' => 'nullable|date|after_or_equal:check_in_date',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if already linked
        $existing = DB::table('group_accommodations')
            ->where('group_id', $group_id)
            ->where('accommodation_id', $request->accommodation_id)
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'Accommodation is already linked to this group.'], 400);
        }

        // Verify accommodation is linked to the trip
        $tripHasAccommodation = DB::table('trip_accommodations')
            ->where('trip_id', $group->trip_id)
            ->where('accommodation_id', $request->accommodation_id)
            ->exists();

        if (!$tripHasAccommodation) {
            return response()->json(['message' => 'Accommodation must first be linked to the trip.'], 400);
        }

        // Link accommodation to group
        $group->accommodations()->attach($request->accommodation_id, [
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'notes' => $request->notes,
            'assigned_by' => Auth::id(),
        ]);

        $accommodation = Accommodation::find($request->accommodation_id);

        return response()->json([
            'message' => 'Accommodation linked to group successfully.',
            'group_id' => $group->group_id,
            'accommodation' => [
                'accommodation_id' => $accommodation->accommodation_id,
                'hotel_name' => $accommodation->hotel_name,
                'city' => $accommodation->city,
            ],
        ], 201);
    }

    /**
     * Update accommodation assignment for a group.
     */
    public function update(Request $request, $group_id, $accommodation_id)
    {
        $group = GroupTrip::findOrFail($group_id);

        // Check if linked
        $existing = DB::table('group_accommodations')
            ->where('group_id', $group_id)
            ->where('accommodation_id', $accommodation_id)
            ->exists();

        if (!$existing) {
            return response()->json(['message' => 'Accommodation is not linked to this group.'], 404);
        }

        $request->validate([
            'check_in_date' => 'nullable|date',
            'check_out_date' => 'nullable|date|after_or_equal:check_in_date',
            'notes' => 'nullable|string|max:500',
        ]);

        // Update pivot data
        $group->accommodations()->updateExistingPivot($accommodation_id, [
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Accommodation assignment updated successfully.',
        ]);
    }

    /**
     * Unlink an accommodation from a group.
     */
    public function unlink($group_id, $accommodation_id)
    {
        $group = GroupTrip::findOrFail($group_id);

        // Check if linked
        $existing = DB::table('group_accommodations')
            ->where('group_id', $group_id)
            ->where('accommodation_id', $accommodation_id)
            ->exists();

        if (!$existing) {
            return response()->json(['message' => 'Accommodation is not linked to this group.'], 404);
        }

        $group->accommodations()->detach($accommodation_id);

        return response()->json(['message' => 'Accommodation unlinked from group successfully.']);
    }

    /**
     * Link accommodation to multiple groups at once.
     */
    public function bulkLink(Request $request)
    {
        $request->validate([
            'accommodation_id' => 'required|exists:accommodations,accommodation_id',
            'group_ids' => 'required|array|min:1',
            'group_ids.*' => 'exists:groups_trips,group_id',
            'check_in_date' => 'nullable|date',
            'check_out_date' => 'nullable|date|after_or_equal:check_in_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $linked = [];
        $failed = [];

        foreach ($request->group_ids as $groupId) {
            $group = GroupTrip::find($groupId);

            // Check if already linked
            $existing = DB::table('group_accommodations')
                ->where('group_id', $groupId)
                ->where('accommodation_id', $request->accommodation_id)
                ->exists();

            if ($existing) {
                $failed[] = ['group_id' => $groupId, 'reason' => 'Already linked'];
                continue;
            }

            // Check trip association
            $tripHasAccommodation = DB::table('trip_accommodations')
                ->where('trip_id', $group->trip_id)
                ->where('accommodation_id', $request->accommodation_id)
                ->exists();

            if (!$tripHasAccommodation) {
                $failed[] = ['group_id' => $groupId, 'reason' => 'Accommodation not in trip'];
                continue;
            }

            $group->accommodations()->attach($request->accommodation_id, [
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'notes' => $request->notes,
                'assigned_by' => Auth::id(),
            ]);

            $linked[] = $groupId;
        }

        return response()->json([
            'message' => 'Bulk link operation completed.',
            'linked_count' => count($linked),
            'failed_count' => count($failed),
            'linked_groups' => $linked,
            'failed_groups' => $failed,
        ]);
    }
}
