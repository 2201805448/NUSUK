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
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($announcements);
    }

    /**
     * Store a newly created announcement (Admin only).
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403, 'Unauthorized. Admins only.');
        }

        $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string',
            'expiry_date' => 'nullable|date',
            'image_url' => 'nullable|string',
        ]);

        $announcement = Announcement::create([
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $request->image_url,
            'expiry_date' => $request->expiry_date,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Announcement created successfully',
            'data' => $announcement
        ], 201);
    }
}
