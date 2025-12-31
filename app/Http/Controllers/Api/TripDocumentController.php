<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TripDocument;
use App\Models\Pilgrim;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TripDocumentController extends Controller
{
    /**
     * List all documents available for a trip (for pilgrims).
     */
    public function index($trip_id)
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Verify the pilgrim is part of this trip
        $membership = GroupMember::with('groupTrip')
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->whereHas('groupTrip', function ($q) use ($trip_id) {
                $q->where('trip_id', $trip_id);
            })
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'You are not registered for this trip.'], 403);
        }

        // Get all public documents for this trip
        $documents = TripDocument::where('trip_id', $trip_id)
            ->where('is_public', true)
            ->orderBy('document_type')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group documents by type
        $grouped = $documents->groupBy('document_type');

        $formattedDocuments = $documents->map(function ($doc) {
            return [
                'document_id' => $doc->document_id,
                'title' => $doc->title,
                'description' => $doc->description,
                'document_type' => $doc->document_type,
                'file_name' => $doc->file_name,
                'file_type' => $doc->file_type,
                'file_size' => $doc->file_size,
                'file_size_human' => $doc->file_size_human,
                'uploaded_at' => $doc->created_at,
            ];
        });

        return response()->json([
            'message' => 'Trip documents retrieved successfully.',
            'trip_id' => $trip_id,
            'total_documents' => $documents->count(),
            'documents_by_type' => $grouped->map(function ($docs, $type) {
                return [
                    'type' => $type,
                    'count' => $docs->count(),
                    'documents' => $docs->map(function ($doc) {
                        return [
                            'document_id' => $doc->document_id,
                            'title' => $doc->title,
                            'file_name' => $doc->file_name,
                            'file_size_human' => $doc->file_size_human,
                        ];
                    }),
                ];
            })->values(),
            'documents' => $formattedDocuments,
        ]);
    }

    /**
     * Get document details (for pilgrims).
     */
    public function show($trip_id, $document_id)
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Verify the pilgrim is part of this trip
        $membership = GroupMember::whereHas('groupTrip', function ($q) use ($trip_id) {
            $q->where('trip_id', $trip_id);
        })->where('pilgrim_id', $pilgrim->pilgrim_id)->first();

        if (!$membership) {
            return response()->json(['message' => 'You are not registered for this trip.'], 403);
        }

        $document = TripDocument::where('trip_id', $trip_id)
            ->where('document_id', $document_id)
            ->where('is_public', true)
            ->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        return response()->json([
            'message' => 'Document details retrieved successfully.',
            'document' => [
                'document_id' => $document->document_id,
                'title' => $document->title,
                'description' => $document->description,
                'document_type' => $document->document_type,
                'file_name' => $document->file_name,
                'file_type' => $document->file_type,
                'file_size' => $document->file_size,
                'file_size_human' => $document->file_size_human,
                'uploaded_at' => $document->created_at,
                'download_url' => url("/api/trips/{$trip_id}/documents/{$document_id}/download"),
            ],
        ]);
    }

    /**
     * Download a document (for pilgrims).
     */
    public function download($trip_id, $document_id)
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Verify the pilgrim is part of this trip
        $membership = GroupMember::whereHas('groupTrip', function ($q) use ($trip_id) {
            $q->where('trip_id', $trip_id);
        })->where('pilgrim_id', $pilgrim->pilgrim_id)->first();

        if (!$membership) {
            return response()->json(['message' => 'You are not registered for this trip.'], 403);
        }

        $document = TripDocument::where('trip_id', $trip_id)
            ->where('document_id', $document_id)
            ->where('is_public', true)
            ->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        // Check if file exists
        $filePath = storage_path('app/' . $document->file_path);

        if (!file_exists($filePath)) {
            // Return document info with error for demo purposes
            return response()->json([
                'message' => 'File not found on server. Contact support.',
                'document' => [
                    'document_id' => $document->document_id,
                    'title' => $document->title,
                    'file_name' => $document->file_name,
                ],
            ], 404);
        }

        return response()->download($filePath, $document->file_name);
    }

    /**
     * Upload a document (for admin/supervisor).
     */
    public function store(Request $request, $trip_id)
    {
        $user = Auth::user();

        // Only ADMIN and SUPERVISOR can upload
        if (!in_array($user->role, ['ADMIN', 'SUPERVISOR'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'document_type' => 'required|in:PROGRAM,INSTRUCTIONS,VISA,TICKET,MAP,GUIDE,OTHER',
            'file' => 'required|file|max:10240', // 10MB max
            'is_public' => 'nullable|boolean',
        ]);

        // Store the file
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store("trip_documents/{$trip_id}");

        $document = TripDocument::create([
            'trip_id' => $trip_id,
            'title' => $request->title,
            'description' => $request->description,
            'document_type' => $request->document_type,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'is_public' => $request->is_public ?? true,
            'uploaded_by' => $user->user_id,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'document' => [
                'document_id' => $document->document_id,
                'title' => $document->title,
                'document_type' => $document->document_type,
                'file_name' => $document->file_name,
                'file_size_human' => $document->file_size_human,
            ],
        ], 201);
    }

    /**
     * Delete a document (for admin).
     */
    public function destroy($trip_id, $document_id)
    {
        $user = Auth::user();

        if ($user->role !== 'ADMIN') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $document = TripDocument::where('trip_id', $trip_id)
            ->where('document_id', $document_id)
            ->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        // Delete the file
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully.']);
    }
}
