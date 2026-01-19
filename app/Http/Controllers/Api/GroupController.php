<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupTrip;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    // List groups (optionally filtered by trip) with details
    public function index(Request $request, $trip_id = null)
    {
        $query = GroupTrip::with(['supervisor']);

        // Check route param first, then query param
        if ($trip_id) {
            $query->where('trip_id', $trip_id);
        } elseif ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        // Role-based filtering
        $user = Auth::user();
        if ($user->role === 'SUPERVISOR') {
            $query->where('supervisor_id', $user->user_id);
        }
        // Admin sees all (no extra filter)

        $groups = $query->get();
        // DEBUG: Attach role info to first item if exists or wrap response
        if ($request->has('debug')) {
            return response()->json([
                'role' => $user->role,
                'id' => $user->user_id,
                'supervisor_id_filter' => ($user->role === 'SUPERVISOR' ? $user->user_id : 'NONE'),
                'data' => $groups
            ]);
        }
        return response()->json($groups);
    }

    // Create a new group (Global endpoint)
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255', // Maps to group_code
            'trip_id' => 'required|exists:trips,trip_id',
            'pilgrim_ids' => 'required|array',
            'pilgrim_ids.*' => 'exists:users,user_id'
        ]);

        // Create Group
        // Assuming current user is supervisor if not admin? 
        // Or if 'supervisor_id' is not passed, maybe default to Auth::id() or null?
        // The user request didn't specify supervisor, so we'll default to Auth::id() if applicable or leave it if nullable.
        // Migration says supervisor_id is unsignedBigInteger.

        $group = GroupTrip::create([
            'group_code' => $validated['name'],
            'trip_id' => $validated['trip_id'],
            'supervisor_id' => Auth::id(), // Assign creator as supervisor default
            'group_status' => 'ACTIVE'
        ]);

        // Link Pilgrims
        $pilgrimIdsToSync = [];
        foreach ($validated['pilgrim_ids'] as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $pilgrim = \App\Models\Pilgrim::firstOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'passport_name' => $user->full_name,
                        'passport_number' => 'TEMP-' . $user->user_id,
                        'nationality' => 'Unknown'
                    ]
                );
                $pilgrimIdsToSync[] = $pilgrim->pilgrim_id;
            }
        }

        // Sync (attach) pilgrims to the group
        // Note: sync() replaces existing. Since it's a new group, it's fine.
        $group->pilgrims()->sync($pilgrimIdsToSync);

        return response()->json([
            'message' => 'Group created successfully!',
            'group' => $group->load('trip', 'pilgrims.user')
        ], 201);
    }

    // Create a new group for a trip
    public function store(Request $request, $trip_id)
    {
        $trip = Trip::findOrFail($trip_id);

        $request->validate([
            'group_code' => 'required|string|max:50|unique:groups_trips,group_code',
            'group_status' => 'in:ACTIVE,FINISHED',
        ]);

        $group = GroupTrip::create([
            'trip_id' => $trip->trip_id,
            'supervisor_id' => Auth::id(),
            'group_code' => $request->group_code,
            'group_status' => $request->group_status ?? 'ACTIVE',
        ]);

        return response()->json([
            'message' => 'Group created successfully',
            'group' => $group
        ], 201);
    }

    // Get specific group details with members
    public function show($id)
    {
        $group = GroupTrip::with(['members.pilgrim.user'])->findOrFail($id);

        // Optional: Check if the authenticated user is the supervisor of this group or an Admin
        if (Auth::user()->role !== 'ADMIN' && $group->supervisor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this group.'], 403);
        }

        return response()->json($group);
    }

    // Update group details
    public function update(Request $request, $id)
    {
        $group = GroupTrip::findOrFail($id);

        // Authorization Check
        if (Auth::user()->role !== 'ADMIN' && $group->supervisor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this group.'], 403);
        }

        $request->validate([
            'group_code' => 'sometimes|string|max:50|unique:groups_trips,group_code,' . $group->group_id . ',group_id',
            'group_status' => 'in:ACTIVE,FINISHED',
        ]);

        $group->update($request->only(['group_code', 'group_status']));

        return response()->json([
            'message' => 'Group updated successfully',
            'group' => $group
        ]);
    }

    // Add Pilgrim to Group
    public function addMember(Request $request, $id)
    {
        $group = GroupTrip::findOrFail($id);

        if (Auth::user()->role !== 'ADMIN' && $group->supervisor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this group.'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,user_id', // Accept User ID
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);

        // Find or Create Pilgrim Record
        $pilgrim = \App\Models\Pilgrim::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'passport_name' => $user->full_name,
                'passport_number' => 'TEMP-' . $user->user_id, // Placeholder
                'nationality' => 'Unknown',
            ]
        );

        // Check if pilgrim is already in the group
        $exists = \App\Models\GroupMember::where('group_id', $group->group_id)
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Pilgrim is already in this group.'], 400);
        }

        $member = \App\Models\GroupMember::create([
            'group_id' => $group->group_id,
            'pilgrim_id' => $pilgrim->pilgrim_id,
            'join_date' => now(),
            'member_status' => 'ACTIVE',
        ]);

        return response()->json([
            'message' => 'Pilgrim added to group successfully',
            'member' => $member
        ], 201);
    }

    // Transfer Pilgrim between groups
    public function transferMember(Request $request, $id)
    {
        $sourceGroup = GroupTrip::findOrFail($id);

        $request->validate([
            'target_group_id' => 'required|exists:groups_trips,group_id',
            'user_id' => 'required|exists:users,user_id',
        ]);

        $targetGroup = GroupTrip::findOrFail($request->target_group_id);

        // Validation: Must be same trip
        if ($sourceGroup->trip_id !== $targetGroup->trip_id) {
            return response()->json(['message' => 'Cannot transfer between groups of different trips.'], 400);
        }

        // Authorization: Supervisor must own both groups OR be Admin
        $isAuthorized = (Auth::user()->role === 'ADMIN') ||
            ($sourceGroup->supervisor_id === Auth::id() && $targetGroup->supervisor_id === Auth::id());

        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized transfer.'], 403);
        }

        // Get Pilgrim ID
        $user = \App\Models\User::findOrFail($request->user_id);
        $pilgrim = \App\Models\Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Find Member in Source
        $member = \App\Models\GroupMember::where('group_id', $sourceGroup->group_id)
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'Pilgrim is not in the source group.'], 404);
        }

        // Check availability in target (duplicates)
        $exists = \App\Models\GroupMember::where('group_id', $targetGroup->group_id)
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Pilgrim is already in the target group.'], 400);
        }

        // Update Group ID
        // Note: Since primary key is composite (group_id, pilgrim_id), updating group_id might require deleting and re-inserting or raw update depending on Model setup.
        // GroupMember model says $primaryKey = null; so 'save' might work if we have the instance.
        // But updating path of PK is risky in Eloquent. Safer to Delete Source -> Create Target, OR use DB::table update.
        // Given existing constraints, I will do Delete + Create to be safe and maintain 'join_date' if needed (or reset).
        // Actually, let's try raw update on the query builder to avoid Eloquent PK confusion.

        \App\Models\GroupMember::where('group_id', $sourceGroup->group_id)
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->update(['group_id' => $targetGroup->group_id]);

        $member = \App\Models\GroupMember::where('group_id', $targetGroup->group_id)
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->first();

        return response()->json([
            'message' => 'Pilgrim transferred successfully',
            'member' => $member
        ]);
    }

    // Remove Pilgrim from Group
    public function removeMember(Request $request, $id)
    {
        $group = GroupTrip::findOrFail($id);

        if (Auth::user()->role !== 'ADMIN' && $group->supervisor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this group.'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,user_id',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        $pilgrim = \App\Models\Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        $member = \App\Models\GroupMember::where('group_id', $group->group_id)
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'Pilgrim is not in this group.'], 404);
        }

        // Logic: Delete record OR Update status to REMOVED?
        // Migration has 'member_status' ENUM('ACTIVE', 'REMOVED').
        // So we update status to REMOVED.

        // Actually, composite key usage in update might be tricky if not careful, 
        // but since we fetched the model ($member), we can (try to) use update().
        // However, GroupMember model has $primaryKey = null.
        // So $member->update() might fail to generate correct WHERE clause.
        // Safer to use query builder again.

        \App\Models\GroupMember::where('group_id', $group->group_id)
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->update(['member_status' => 'REMOVED']);

        return response()->json([
            'message' => 'Pilgrim removed from group successfully'
        ]);
    }

    public function assignSupervisor(Request $request, GroupTrip $group)
    {
        $validated = $request->validate([
            'supervisor_id' => 'required|exists:users,user_id'
        ]);

        $group->update([
            'supervisor_id' => $validated['supervisor_id']
        ]);

        return response()->json([
            'message' => 'Supervisor assigned successfully',
            'group' => $group->load('supervisor')
        ]);
    }

    // Unassign Supervisor from Group (Admin Only)
    public function unassignSupervisor($id)
    {
        // Only Admin can unassign supervisors
        if (Auth::user()->role !== 'ADMIN') {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $group = GroupTrip::findOrFail($id);

        $group->supervisor_id = null;
        $group->save();

        return response()->json([
            'message' => 'Supervisor unassigned successfully.',
            'group' => $group
        ]);
    }

    /**
     * List pilgrims in a specific group (for Supervisor/Admin)
     */
    public function listPilgrims($id)
    {
        $group = GroupTrip::with(['trip', 'supervisor', 'members.pilgrim.user'])
            ->findOrFail($id);

        // Authorization: Must be supervisor of this group or Admin
        if (Auth::user()->role !== 'ADMIN' && $group->supervisor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this group.'], 403);
        }

        // Format pilgrims list
        $pilgrims = $group->members->map(function ($member) {
            $pilgrim = $member->pilgrim;
            $user = $pilgrim ? $pilgrim->user : null;

            return [
                'pilgrim_id' => $pilgrim->pilgrim_id ?? null,
                'user_id' => $user->user_id ?? null,
                'full_name' => $user->full_name ?? $pilgrim->passport_name ?? 'Unknown',
                'email' => $user->email ?? null,
                'phone_number' => $user->phone_number ?? null,
                'passport_name' => $pilgrim->passport_name ?? null,
                'passport_number' => $pilgrim->passport_number ?? null,
                'nationality' => $pilgrim->nationality ?? null,
                'gender' => $pilgrim->gender ?? null,
                'date_of_birth' => $pilgrim->date_of_birth ?? null,
                'emergency_call' => $pilgrim->emergency_call ?? null,
                'join_date' => $member->join_date,
                'member_status' => $member->member_status,
            ];
        });

        // Filter by status (active only by default)
        $activePilgrims = $pilgrims->where('member_status', 'ACTIVE');
        $removedPilgrims = $pilgrims->where('member_status', 'REMOVED');

        return response()->json([
            'message' => 'Pilgrims list retrieved successfully.',
            'group' => [
                'group_id' => $group->group_id,
                'group_code' => $group->group_code,
                'group_status' => $group->group_status,
                'trip' => $group->trip ? [
                    'trip_id' => $group->trip->trip_id,
                    'trip_name' => $group->trip->trip_name,
                    'start_date' => $group->trip->start_date,
                    'end_date' => $group->trip->end_date,
                ] : null,
                'supervisor' => $group->supervisor ? [
                    'user_id' => $group->supervisor->user_id,
                    'full_name' => $group->supervisor->full_name,
                ] : null,
            ],
            'summary' => [
                'total_pilgrims' => $pilgrims->count(),
                'active_pilgrims' => $activePilgrims->count(),
                'removed_pilgrims' => $removedPilgrims->count(),
            ],
            'pilgrims' => $activePilgrims->values(),
        ]);
    }

    /**
     * List all pilgrims in groups supervised by the current supervisor
     * OR all pilgrims in a trip (for Admin)
     */
    public function listAllPilgrims(Request $request)
    {
        $user = Auth::user();

        $query = GroupTrip::with(['trip', 'members.pilgrim.user']);

        // Filter by trip if provided
        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        // Role-based filtering
        if ($user->role === 'SUPERVISOR') {
            $query->where('supervisor_id', $user->user_id);
        }
        // Admin sees all groups

        $groups = $query->get();

        // Collect all pilgrims across groups
        $allPilgrims = collect();

        foreach ($groups as $group) {
            foreach ($group->members as $member) {
                $pilgrim = $member->pilgrim;
                $pilgrimUser = $pilgrim ? $pilgrim->user : null;

                $allPilgrims->push([
                    'pilgrim_id' => $pilgrim->pilgrim_id ?? null,
                    'user_id' => $pilgrimUser->user_id ?? null,
                    'full_name' => $pilgrimUser->full_name ?? $pilgrim->passport_name ?? 'Unknown',
                    'email' => $pilgrimUser->email ?? null,
                    'phone_number' => $pilgrimUser->phone_number ?? null,
                    'passport_name' => $pilgrim->passport_name ?? null,
                    'nationality' => $pilgrim->nationality ?? null,
                    'gender' => $pilgrim->gender ?? null,
                    'group_id' => $group->group_id,
                    'group_code' => $group->group_code,
                    'trip_id' => $group->trip_id,
                    'trip_name' => $group->trip->trip_name ?? null,
                    'join_date' => $member->join_date,
                    'member_status' => $member->member_status,
                ]);
            }
        }

        // Filter active only
        $activePilgrims = $allPilgrims->where('member_status', 'ACTIVE');

        return response()->json([
            'message' => 'All pilgrims retrieved successfully.',
            'filter' => [
                'trip_id' => $request->trip_id ?? 'all',
                'role' => $user->role,
            ],
            'summary' => [
                'total_groups' => $groups->count(),
                'total_pilgrims' => $allPilgrims->count(),
                'active_pilgrims' => $activePilgrims->count(),
            ],
            'pilgrims' => $activePilgrims->values(),
        ]);
    }
}

