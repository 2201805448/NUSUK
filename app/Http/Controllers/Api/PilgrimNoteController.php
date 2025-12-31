<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PilgrimNote;
use App\Models\Pilgrim;
use App\Models\GroupMember;
use App\Models\GroupTrip;
use Illuminate\Support\Facades\Auth;

class PilgrimNoteController extends Controller
{
    /**
     * Submit a note from a pilgrim.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Find pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        $request->validate([
            'trip_id' => 'required|exists:trips,trip_id',
            'note_type' => 'required|in:FEEDBACK,SUGGESTION,COMPLAINT,REQUEST,OBSERVATION,OTHER',
            'note_text' => 'required|string|min:10',
            'category' => 'nullable|in:ACCOMMODATION,TRANSPORT,FOOD,SCHEDULE,SERVICE,STAFF,GENERAL',
            'priority' => 'nullable|in:LOW,MEDIUM,HIGH',
        ]);

        // Get pilgrim's group for this trip
        $membership = GroupMember::with('groupTrip')
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->whereHas('groupTrip', function ($q) use ($request) {
                $q->where('trip_id', $request->trip_id);
            })
            ->first();

        $note = PilgrimNote::create([
            'pilgrim_id' => $pilgrim->pilgrim_id,
            'trip_id' => $request->trip_id,
            'group_id' => $membership ? $membership->groupTrip->group_id : null,
            'note_type' => $request->note_type,
            'note_text' => $request->note_text,
            'category' => $request->category ?? 'GENERAL',
            'priority' => $request->priority ?? 'MEDIUM',
            'status' => 'PENDING',
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Note submitted successfully.',
            'note' => [
                'note_id' => $note->note_id,
                'note_type' => $note->note_type,
                'category' => $note->category,
                'priority' => $note->priority,
                'status' => $note->status,
                'created_at' => $note->created_at,
            ]
        ], 201);
    }

    /**
     * View notes submitted by the pilgrim.
     */
    public function myNotes(Request $request)
    {
        $user = Auth::user();

        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        $query = PilgrimNote::with('trip')
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->orderBy('created_at', 'desc');

        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $notes = $query->get();

        return response()->json([
            'message' => 'Your notes retrieved successfully.',
            'total' => $notes->count(),
            'notes' => $notes->map(function ($note) {
                return [
                    'note_id' => $note->note_id,
                    'trip_name' => $note->trip->trip_name ?? null,
                    'note_type' => $note->note_type,
                    'category' => $note->category,
                    'note_text' => $note->note_text,
                    'priority' => $note->priority,
                    'status' => $note->status,
                    'response' => $note->response,
                    'created_at' => $note->created_at,
                    'reviewed_at' => $note->reviewed_at,
                ];
            }),
        ]);
    }

    /**
     * View all notes from pilgrims (for Supervisor/Admin).
     * Supervisors see notes from pilgrims in their groups.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = PilgrimNote::with(['pilgrim.user', 'trip', 'group'])
            ->orderBy('created_at', 'desc');

        // Role-based filtering
        if ($user->role === 'SUPERVISOR') {
            // Get groups supervised by this user
            $supervisedGroupIds = GroupTrip::where('supervisor_id', $user->user_id)
                ->pluck('group_id');

            $query->whereIn('group_id', $supervisedGroupIds);
        }
        // Admin sees all

        // Filters
        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('note_type')) {
            $query->where('note_type', $request->note_type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $notes = $query->get();

        // Group by category for analysis
        $byCategory = $notes->groupBy('category')->map->count();
        $byType = $notes->groupBy('note_type')->map->count();
        $byPriority = $notes->groupBy('priority')->map->count();
        $byStatus = $notes->groupBy('status')->map->count();

        return response()->json([
            'message' => 'Pilgrim notes retrieved successfully.',
            'summary' => [
                'total_notes' => $notes->count(),
                'pending' => $notes->where('status', 'PENDING')->count(),
                'reviewed' => $notes->where('status', 'REVIEWED')->count(),
                'resolved' => $notes->where('status', 'RESOLVED')->count(),
            ],
            'analysis' => [
                'by_category' => $byCategory,
                'by_type' => $byType,
                'by_priority' => $byPriority,
                'by_status' => $byStatus,
            ],
            'notes' => $notes->map(function ($note) {
                return [
                    'note_id' => $note->note_id,
                    'pilgrim' => [
                        'pilgrim_id' => $note->pilgrim->pilgrim_id ?? null,
                        'name' => $note->pilgrim->user->full_name ?? $note->pilgrim->passport_name ?? 'Unknown',
                    ],
                    'trip' => [
                        'trip_id' => $note->trip->trip_id ?? null,
                        'trip_name' => $note->trip->trip_name ?? null,
                    ],
                    'group_code' => $note->group->group_code ?? null,
                    'note_type' => $note->note_type,
                    'category' => $note->category,
                    'note_text' => $note->note_text,
                    'priority' => $note->priority,
                    'status' => $note->status,
                    'created_at' => $note->created_at,
                    'response' => $note->response,
                    'reviewed_at' => $note->reviewed_at,
                ];
            }),
        ]);
    }

    /**
     * View a specific note (for Supervisor/Admin).
     */
    public function show($note_id)
    {
        $user = Auth::user();

        $note = PilgrimNote::with(['pilgrim.user', 'trip', 'group', 'reviewer'])
            ->find($note_id);

        if (!$note) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        // Authorization
        if ($user->role === 'SUPERVISOR') {
            $supervisedGroupIds = GroupTrip::where('supervisor_id', $user->user_id)
                ->pluck('group_id')
                ->toArray();

            if (!in_array($note->group_id, $supervisedGroupIds)) {
                return response()->json(['message' => 'Unauthorized access to this note.'], 403);
            }
        }

        return response()->json([
            'message' => 'Note details retrieved successfully.',
            'note' => [
                'note_id' => $note->note_id,
                'pilgrim' => [
                    'pilgrim_id' => $note->pilgrim->pilgrim_id ?? null,
                    'name' => $note->pilgrim->user->full_name ?? $note->pilgrim->passport_name ?? 'Unknown',
                    'phone' => $note->pilgrim->user->phone_number ?? null,
                    'nationality' => $note->pilgrim->nationality ?? null,
                ],
                'trip' => [
                    'trip_id' => $note->trip->trip_id ?? null,
                    'trip_name' => $note->trip->trip_name ?? null,
                ],
                'group_code' => $note->group->group_code ?? null,
                'note_type' => $note->note_type,
                'category' => $note->category,
                'note_text' => $note->note_text,
                'priority' => $note->priority,
                'status' => $note->status,
                'created_at' => $note->created_at,
                'reviewed_by' => $note->reviewer->full_name ?? null,
                'response' => $note->response,
                'reviewed_at' => $note->reviewed_at,
            ],
        ]);
    }

    /**
     * Update note status and add response (for Supervisor/Admin).
     */
    public function respond(Request $request, $note_id)
    {
        $user = Auth::user();

        $note = PilgrimNote::find($note_id);

        if (!$note) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        // Authorization
        if ($user->role === 'SUPERVISOR') {
            $supervisedGroupIds = GroupTrip::where('supervisor_id', $user->user_id)
                ->pluck('group_id')
                ->toArray();

            if (!in_array($note->group_id, $supervisedGroupIds)) {
                return response()->json(['message' => 'Unauthorized access to this note.'], 403);
            }
        }

        $request->validate([
            'status' => 'required|in:REVIEWED,RESOLVED,DISMISSED',
            'response' => 'nullable|string',
        ]);

        $note->status = $request->status;
        $note->response = $request->response;
        $note->reviewed_by = $user->user_id;
        $note->reviewed_at = now();
        $note->save();

        return response()->json([
            'message' => 'Note updated successfully.',
            'note' => [
                'note_id' => $note->note_id,
                'status' => $note->status,
                'response' => $note->response,
                'reviewed_at' => $note->reviewed_at,
            ],
        ]);
    }
}
