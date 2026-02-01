<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReligiousContent;

class GuideController extends Controller
{
    /**
     * Display a listing of religious content.
     * Filterable by 'category'.
     */
    public function index(Request $request)
    {
        $query = ReligiousContent::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $content = $query->orderBy('created_at', 'desc')->get();
        // Return empty array if empty (Collection behaves this way)
        return response()->json($content);
    }

    /**
     * Display the specified religious content.
     */
    public function show($id)
    {
        $content = ReligiousContent::findOrFail($id);
        return response()->json($content);
    }

    /**
     * Create content (Helper for seeding/testing - Admin only)
     * In a real app, this might be in a separate Admin-only controller or protected here.
     * We'll implement it here for completeness to allow creating the content for the test.
     */
    public function store(Request $request)
    {
        // Ideally check for admin role here if exposed
        $request->validate([
            'title' => 'required|string',
            'category' => 'required|in:PRAYER,GUIDE,HADITH,GENERAL,DUA,ATHKAR',
            'body_text' => 'required|string',
        ]);

        $content = ReligiousContent::create($request->all());

        return response()->json($content, 201);
    }
}
