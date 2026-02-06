<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transport;
use App\Models\Trip;

class TransportController extends Controller
{
    /**
     * Display a listing of transports.
     * Filterable by trip_id.
     */
    public function index(Request $request)
    {
        $query = Transport::with(['route', 'driver', 'routeFrom', 'routeTo']);

        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created transport record for a trip.
     */
    public function store(Request $request)
    {
        $request->validate([
            'trip_id' => 'nullable|exists:trips,trip_id',
            'driver_id' => 'nullable|exists:drivers,driver_id',
            'route_id' => 'nullable|exists:transport_routes,id',
            'transport_type' => 'required|string|max:50',
            'route_from' => 'nullable|integer|exists:transport_routes,id',
            'route_to' => 'nullable|integer|exists:transport_routes,id',
            'departure_time' => 'required|date',
            'arrival_time' => 'nullable|date|after:departure_time',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();

        // DEBUG: Log incoming data to check if arrival_time is being sent
        \Log::info('Transport Store - Received Data:', $data);

        // Auto-fill from route if linked and fields are missing
        if ($request->filled('route_id')) {
            $route = \App\Models\TransportRoute::find($request->route_id);
            if ($route) {
                // Auto-calculate arrival_time if not provided
                if (!$request->filled('arrival_time') && $request->filled('departure_time') && $route->estimated_duration_mins) {
                    $departureTime = \Carbon\Carbon::parse($request->departure_time);
                    $data['arrival_time'] = $departureTime->addMinutes($route->estimated_duration_mins);
                }
            }
        }

        $transport = Transport::create($data);

        return response()->json([
            'message' => 'Transport added successfully',
            'transport' => $transport
        ], 201);
    }

    /**
     * Display the specified transport.
     */
    public function show($id)
    {
        $transport = Transport::with(['trip', 'driver', 'route'])->findOrFail($id);
        return response()->json($transport);
    }

    /**
     * Update the specified transport in storage.
     */
    public function update(Request $request, $id)
    {
        $transport = Transport::findOrFail($id);

        $request->validate([
            'trip_id' => 'sometimes|exists:trips,trip_id',
            'driver_id' => 'nullable|exists:drivers,driver_id',
            'route_id' => 'nullable|exists:transport_routes,id',
            'transport_type' => 'sometimes|string|max:50',
            'route_from' => 'nullable|integer|exists:transport_routes,id',
            'route_to' => 'nullable|integer|exists:transport_routes,id',
            'departure_time' => 'sometimes|date',
            'arrival_time' => 'nullable|date|after:departure_time',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();

        // If simply updating fields without changing route logic significantly, standard update works but need to be careful with nulls if not provided. 
        // Actually, simplest approach:

        $transport->fill($request->all());

        // Re-apply route logic if route_id is being changed or if specific route fields requested. 
        // If the user sends route_id, we might want to refresh from/to if they aren't sent.
        if ($request->has('route_id') && $request->route_id) {
            $route = \App\Models\TransportRoute::find($request->route_id);
            if ($route) {
                if (!$request->filled('route_from'))
                    $transport->route_from = $route->start_location;
                if (!$request->filled('route_to'))
                    $transport->route_to = $route->end_location;

                // Auto-calculate arrival_time if not provided
                $departureTime = $request->filled('departure_time')
                    ? $request->departure_time
                    : $transport->departure_time;

                if (!$request->filled('arrival_time') && $departureTime && $route->estimated_duration_mins) {
                    $transport->arrival_time = \Carbon\Carbon::parse($departureTime)->addMinutes($route->estimated_duration_mins);
                }
            }
        }

        $transport->save();

        return response()->json([
            'message' => 'Transport updated successfully',
            'transport' => $transport
        ]);
    }

    /**
     * Remove the specified transport.
     */
    public function destroy($id)
    {
        $transport = Transport::findOrFail($id);
        $transport->delete();

        return response()->json([
            'message' => 'Transport deleted successfully'
        ]);
    }
}
