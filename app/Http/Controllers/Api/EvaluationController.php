<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    /**
     * Get all trip evaluations (type = 'TRIP')
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trips(Request $request)
    {
        $query = Evaluation::where('type', 'TRIP')
            ->where('internal_only', false);

        // Optional: Filter by specific trip
        if ($request->has('trip_id')) {
            $query->where('target_id', $request->trip_id);
        }

        // Optional: Filter by score
        if ($request->has('min_score')) {
            $query->where('score', '>=', $request->min_score);
        }

        $evaluations = $query->with([
            'pilgrim' => function ($q) {
                $q->select('pilgrim_id', 'user_id');
                $q->with(['user:id,name']);
            }
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Transform evaluations to include trip details
        $evaluations->getCollection()->transform(function ($evaluation) {
            $trip = Trip::find($evaluation->target_id);
            return [
                'evaluation_id' => $evaluation->evaluation_id,
                'pilgrim_id' => $evaluation->pilgrim_id,
                'pilgrim_name' => $evaluation->pilgrim?->user?->name ?? 'Unknown',
                'type' => $evaluation->type,
                'score' => $evaluation->score,
                'target_id' => $evaluation->target_id,
                'trip_name' => $trip?->name ?? null,
                'concern_text' => $evaluation->concern_text,
                'created_at' => $evaluation->created_at,
            ];
        });

        return response()->json($evaluations);
    }

    /**
     * Get all evaluations with optional filtering
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Evaluation::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', strtoupper($request->type));
        }

        // Filter by internal_only flag
        if ($request->has('internal_only')) {
            $query->where('internal_only', filter_var($request->internal_only, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by target
        if ($request->has('target_id')) {
            $query->where('target_id', $request->target_id);
        }

        $evaluations = $query->with([
            'pilgrim' => function ($q) {
                $q->select('pilgrim_id', 'user_id');
                $q->with(['user:id,name']);
            }
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($evaluations);
    }

    /**
     * Get a specific evaluation
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $evaluation = Evaluation::with([
            'pilgrim' => function ($q) {
                $q->select('pilgrim_id', 'user_id');
                $q->with(['user:id,name']);
            }
        ])->find($id);

        if (!$evaluation) {
            return response()->json(['message' => 'Evaluation not found'], 404);
        }

        return response()->json($evaluation);
    }

    /**
     * Store a new evaluation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:TRIP,HOTEL,SERVICE,SUPPORT',
            'score' => 'required|integer|min:1|max:5',
            'target_id' => 'required|integer',
            'concern_text' => 'nullable|string|max:1000',
            'internal_only' => 'nullable|boolean',
        ]);

        // Get pilgrim_id from authenticated user
        $user = Auth::user();
        $pilgrim = $user->pilgrim ?? null;

        if (!$pilgrim) {
            return response()->json(['message' => 'Only pilgrims can submit evaluations'], 403);
        }

        $evaluation = Evaluation::create([
            'pilgrim_id' => $pilgrim->pilgrim_id,
            'type' => $validated['type'],
            'score' => $validated['score'],
            'target_id' => $validated['target_id'],
            'concern_text' => $validated['concern_text'] ?? null,
            'internal_only' => $validated['internal_only'] ?? true,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Evaluation submitted successfully',
            'evaluation' => $evaluation,
        ], 201);
    }

    /**
     * Get hotel evaluations (type = 'HOTEL')
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hotels(Request $request)
    {
        $query = Evaluation::where('type', 'HOTEL')
            ->where('internal_only', false);

        if ($request->has('hotel_id')) {
            $query->where('target_id', $request->hotel_id);
        }

        $evaluations = $query->with([
            'pilgrim' => function ($q) {
                $q->select('pilgrim_id', 'user_id');
                $q->with(['user:id,name']);
            }
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($evaluations);
    }
}
