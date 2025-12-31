<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pilgrim;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;

class PilgrimDocumentController extends Controller
{
    /**
     * List pilgrims with their documents.
     * ADMIN: Can view all pilgrims, optionally filtered by trip/group.
     * SUPERVISOR: Can only view pilgrims in groups they supervise.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Pilgrim::with('user');

        if ($user->role === 'ADMIN') {
            // Admin can view all, with optional filters
            if ($request->has('trip_id')) {
                // Filter by trip: get pilgrim IDs from group members in this trip
                $pilgrimIds = GroupMember::whereHas('groupTrip', function ($q) use ($request) {
                    $q->where('trip_id', $request->trip_id);
                })->pluck('pilgrim_id');
                $query->whereIn('pilgrim_id', $pilgrimIds);
            }

            if ($request->has('group_id')) {
                // Filter by specific group
                $pilgrimIds = GroupMember::where('group_id', $request->group_id)
                    ->where('member_status', 'ACTIVE')
                    ->pluck('pilgrim_id');
                $query->whereIn('pilgrim_id', $pilgrimIds);
            }
        } elseif ($user->role === 'SUPERVISOR') {
            // Supervisor can only view pilgrims in their supervised groups
            $supervisedGroupIds = GroupTrip::where('supervisor_id', $user->user_id)
                ->pluck('group_id');

            if ($supervisedGroupIds->isEmpty()) {
                return response()->json([
                    'message' => 'No groups assigned to this supervisor.',
                    'data' => []
                ]);
            }

            $pilgrimIds = GroupMember::whereIn('group_id', $supervisedGroupIds)
                ->where('member_status', 'ACTIVE')
                ->pluck('pilgrim_id');

            $query->whereIn('pilgrim_id', $pilgrimIds);

            // Additional filter by trip_id (if provided, only show if supervisor has groups in that trip)
            if ($request->has('trip_id')) {
                $tripGroupIds = GroupTrip::where('supervisor_id', $user->user_id)
                    ->where('trip_id', $request->trip_id)
                    ->pluck('group_id');
                $tripPilgrimIds = GroupMember::whereIn('group_id', $tripGroupIds)
                    ->where('member_status', 'ACTIVE')
                    ->pluck('pilgrim_id');
                $query->whereIn('pilgrim_id', $tripPilgrimIds);
            }

            // Additional filter by group_id (if provided, verify supervisor owns this group)
            if ($request->has('group_id')) {
                $isOwner = GroupTrip::where('group_id', $request->group_id)
                    ->where('supervisor_id', $user->user_id)
                    ->exists();
                if (!$isOwner) {
                    return response()->json(['message' => 'Unauthorized access to this group.'], 403);
                }
                $groupPilgrimIds = GroupMember::where('group_id', $request->group_id)
                    ->where('member_status', 'ACTIVE')
                    ->pluck('pilgrim_id');
                $query->whereIn('pilgrim_id', $groupPilgrimIds);
            }
        } else {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $pilgrims = $query->get()->map(function ($pilgrim) {
            return [
                'pilgrim_id' => $pilgrim->pilgrim_id,
                'full_name' => $pilgrim->user->full_name ?? null,
                'email' => $pilgrim->user->email ?? null,
                'phone_number' => $pilgrim->user->phone_number ?? null,
                'passport_name' => $pilgrim->passport_name,
                'passport_number' => $pilgrim->passport_number,
                'passport_img' => $pilgrim->passport_img,
                'visa_img' => $pilgrim->visa_img,
                'nationality' => $pilgrim->nationality,
                'date_of_birth' => $pilgrim->date_of_birth,
                'gender' => $pilgrim->gender,
                'emergency_call' => $pilgrim->emergency_call,
            ];
        });

        return response()->json([
            'message' => 'Pilgrim documents retrieved successfully.',
            'count' => $pilgrims->count(),
            'data' => $pilgrims
        ]);
    }

    /**
     * View a specific pilgrim's documents.
     * ADMIN: Can view any pilgrim's documents.
     * SUPERVISOR: Can only view documents for pilgrims in their supervised groups.
     */
    public function show($id)
    {
        $user = Auth::user();
        $pilgrim = Pilgrim::with('user')->find($id);

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim not found.'], 404);
        }

        if ($user->role === 'ADMIN') {
            // Admin can view any pilgrim
        } elseif ($user->role === 'SUPERVISOR') {
            // Supervisor can only view pilgrims in their groups
            $supervisedGroupIds = GroupTrip::where('supervisor_id', $user->user_id)
                ->pluck('group_id');

            $isInSupervisedGroup = GroupMember::whereIn('group_id', $supervisedGroupIds)
                ->where('pilgrim_id', $id)
                ->where('member_status', 'ACTIVE')
                ->exists();

            if (!$isInSupervisedGroup) {
                return response()->json(['message' => 'Unauthorized. This pilgrim is not in your supervised groups.'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Get group membership info
        $groupInfo = GroupMember::with('groupTrip.trip')
            ->where('pilgrim_id', $id)
            ->where('member_status', 'ACTIVE')
            ->first();

        $groupData = null;
        if ($groupInfo && $groupInfo->groupTrip) {
            $groupData = [
                'group_id' => $groupInfo->group_id,
                'group_code' => $groupInfo->groupTrip->group_code,
                'trip_name' => $groupInfo->groupTrip->trip->trip_name ?? 'N/A',
                'join_date' => $groupInfo->join_date,
            ];
        }

        return response()->json([
            'message' => 'Pilgrim documents retrieved successfully.',
            'data' => [
                'pilgrim_id' => $pilgrim->pilgrim_id,
                'user_info' => [
                    'user_id' => $pilgrim->user->user_id ?? null,
                    'full_name' => $pilgrim->user->full_name ?? null,
                    'email' => $pilgrim->user->email ?? null,
                    'phone_number' => $pilgrim->user->phone_number ?? null,
                ],
                'documents' => [
                    'passport_name' => $pilgrim->passport_name,
                    'passport_number' => $pilgrim->passport_number,
                    'passport_img' => $pilgrim->passport_img,
                    'visa_img' => $pilgrim->visa_img,
                ],
                'personal_data' => [
                    'nationality' => $pilgrim->nationality,
                    'date_of_birth' => $pilgrim->date_of_birth,
                    'gender' => $pilgrim->gender,
                    'emergency_call' => $pilgrim->emergency_call,
                    'notes' => $pilgrim->notes,
                ],
                'group_info' => $groupData,
            ]
        ]);
    }
}
