<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of active announcements/advertisements.
     */
    public function index()
    {
        $announcements = Announcement::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', now());
            })
            // Filter by start_date if set
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            // Sort by priority (URGENT first) then by creation date
            ->orderByRaw("FIELD(priority, 'URGENT', 'HIGH', 'NORMAL')")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($announcements);
    }

    /**
     * Store a newly created announcement (Admin only).
     */
    public function store(Request $request)
    {
        // Authorization is handled by the 'role:ADMIN' middleware in api.php

        $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string',
            'expiry_date' => 'nullable|date',
            'image_url' => 'nullable|string',
            'type' => 'in:GENERAL,PACKAGE,TRIP,OFFER',
            'priority' => 'in:NORMAL,HIGH,URGENT',
            'start_date' => 'nullable|date',
            'related_id' => 'nullable|integer', // Basic validation, logic handled below
        ]);

        // Validation for related_id based on type
        $type = $request->input('type', 'GENERAL');
        $relatedId = $request->input('related_id');

        if ($type && $type !== 'GENERAL' && $type !== 'OFFER') {
            if (!$relatedId) {
                return response()->json(['message' => 'related_id is required for this announcement type.'], 422);
            }

            // Validate existence
            if ($type === 'PACKAGE') {
                if (!\App\Models\Package::where('package_id', $relatedId)->exists()) {
                    return response()->json(['message' => 'Invalid Package ID.'], 422);
                }
            } elseif ($type === 'TRIP') {
                if (!\App\Models\Trip::where('trip_id', $relatedId)->exists()) {
                    return response()->json(['message' => 'Invalid Trip ID.'], 422);
                }
            }
        }

        $announcement = Announcement::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'image_url' => $request->input('image_url'),
            'expiry_date' => $request->input('expiry_date'),
            'is_active' => true,
            'created_at' => now(),
            'type' => $type,
            'related_id' => $relatedId,
            'priority' => $request->input('priority', 'NORMAL'),
            'start_date' => $request->input('start_date', now()),
        ]);

        return response()->json([
            'message' => 'Announcement created successfully',
            'data' => $announcement
        ], 201);
    }

    /**
     * Update an existing announcement (Admin only).
     */
    public function update(Request $request, $id)
    {
        // Authorization is handled by the 'role:ADMIN' middleware in api.php

        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found.'], 404);
        }

        $request->validate([
            'title' => 'nullable|string|max:150',
            'content' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'image_url' => 'nullable|string',
            'type' => 'in:GENERAL,PACKAGE,TRIP,OFFER',
            'priority' => 'in:NORMAL,HIGH,URGENT',
            'start_date' => 'nullable|date',
            'related_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Determine new values or keep old ones
        $type = $request->input('type', $announcement->type);
        $relatedId = $request->has('related_id') ? $request->input('related_id') : $announcement->related_id;

        // Validation for related_id based on type (if type or related_id changed)
        if ($type !== 'GENERAL' && $type !== 'OFFER') {
            if (!$relatedId) {
                return response()->json(['message' => 'related_id is required for this announcement type.'], 422);
            }

            // Validate existence if changed
            if ($request->has('related_id') || $request->has('type')) {
                if ($type === 'PACKAGE') {
                    if (!\App\Models\Package::where('package_id', $relatedId)->exists()) {
                        return response()->json(['message' => 'Invalid Package ID.'], 422);
                    }
                } elseif ($type === 'TRIP') {
                    if (!\App\Models\Trip::where('trip_id', $relatedId)->exists()) {
                        return response()->json(['message' => 'Invalid Trip ID.'], 422);
                    }
                }
            }
        }

        $announcement->update([
            'title' => $request->input('title', $announcement->title),
            'content' => $request->input('content', $announcement->content),
            'image_url' => $request->input('image_url', $announcement->image_url),
            'expiry_date' => $request->input('expiry_date', $announcement->expiry_date),
            'is_active' => $request->input('is_active', $announcement->is_active),
            'type' => $type,
            'related_id' => $relatedId,
            'priority' => $request->input('priority', $announcement->priority),
            'start_date' => $request->input('start_date', $announcement->start_date),
        ]);

        return response()->json([
            'message' => 'Announcement updated successfully',
            'data' => $announcement
        ]);
    }

    /**
     * Display the specified announcement.
     */
    public function show($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found.'], 404);
        }

        // Fetch related data if applicable
        $relatedData = null;
        if ($announcement->type === 'TRIP' && $announcement->related_id) {
            $relatedData = \App\Models\Trip::find($announcement->related_id);
        } elseif ($announcement->type === 'PACKAGE' && $announcement->related_id) {
            $relatedData = \App\Models\Package::find($announcement->related_id);
        }

        return response()->json([
            'message' => 'Announcement details retrieved successfully',
            'data' => $announcement,
            'related_data' => $relatedData
        ]);
    }

    /**
     * Remove the specified announcement from storage (Admin only).
     */
    public function destroy($id)
    {
        // Authorization is handled by the 'role:ADMIN' middleware in api.php

        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found.'], 404);
        }

        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted successfully.']);
    }
}
