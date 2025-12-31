<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pilgrim;
use App\Models\GroupMember;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TripScheduleController extends Controller
{
    /**
     * View the full timeline of a trip.
     * Includes transportation schedules, visits, daily activities, and trip stages.
     */
    public function show($trip_id)
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

        // Get full trip details with all related data
        $trip = Trip::with([
            'package',
            'activities',
            'transports.driver',
            'transports.route',
            'accommodations'
        ])->find($trip_id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found.'], 404);
        }

        // Get trip updates
        $updates = \App\Models\TripUpdate::where('trip_id', $trip_id)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        // Build the complete timeline
        $timeline = $this->buildTimeline($trip);

        // Build the daily schedule
        $dailySchedule = $this->buildDailySchedule($trip);

        return response()->json([
            'message' => 'Trip schedule retrieved successfully.',
            'trip' => [
                'trip_id' => $trip->trip_id,
                'trip_name' => $trip->trip_name,
                'start_date' => $trip->start_date,
                'end_date' => $trip->end_date,
                'status' => $trip->status,
                'notes' => $trip->notes,
                'duration_days' => $trip->start_date && $trip->end_date
                    ? Carbon::parse($trip->start_date)->diffInDays(Carbon::parse($trip->end_date)) + 1
                    : null,
            ],
            'package' => $trip->package ? [
                'package_id' => $trip->package->package_id,
                'package_name' => $trip->package->package_name ?? null,
                'duration_days' => $trip->package->duration_days ?? null,
                'services' => $trip->package->services ?? null,
            ] : null,
            'group_info' => [
                'group_id' => $membership->groupTrip->group_id,
                'group_code' => $membership->groupTrip->group_code,
                'supervisor' => $membership->groupTrip->supervisor ? [
                    'name' => $membership->groupTrip->supervisor->full_name,
                    'phone' => $membership->groupTrip->supervisor->phone_number,
                ] : null,
            ],
            'accommodations' => $trip->accommodations->map(function ($hotel) {
                return [
                    'accommodation_id' => $hotel->accommodation_id,
                    'hotel_name' => $hotel->hotel_name ?? null,
                    'location' => $hotel->location ?? null,
                    'star_rating' => $hotel->star_rating ?? null,
                    'contact_number' => $hotel->contact_number ?? null,
                ];
            }),
            'transportation' => $trip->transports->map(function ($transport) {
                return [
                    'transport_id' => $transport->transport_id,
                    'transport_type' => $transport->transport_type,
                    'route_from' => $transport->route_from,
                    'route_to' => $transport->route_to,
                    'departure_time' => $transport->departure_time,
                    'arrival_time' => $transport->arrival_time ?? null,
                    'driver' => $transport->driver ? [
                        'name' => $transport->driver->name ?? null,
                        'phone' => $transport->driver->phone ?? null,
                    ] : null,
                    'route_info' => $transport->route ? [
                        'route_name' => $transport->route->route_name ?? null,
                        'distance_km' => $transport->route->distance_km ?? null,
                        'estimated_duration_mins' => $transport->route->estimated_duration_mins ?? null,
                    ] : null,
                    'notes' => $transport->notes,
                ];
            })->sortBy('departure_time')->values(),
            'activities_summary' => [
                'total' => $trip->activities->count(),
                'scheduled' => $trip->activities->where('status', 'SCHEDULED')->count(),
                'completed' => $trip->activities->where('status', 'DONE')->count(),
                'cancelled' => $trip->activities->where('status', 'CANCELLED')->count(),
            ],
            'timeline' => $timeline,
            'daily_schedule' => $dailySchedule,
            'updates' => $updates->map(function ($update) {
                return [
                    'update_id' => $update->update_id,
                    'title' => $update->title,
                    'message' => $update->message,
                    'created_at' => $update->created_at,
                    'created_by' => $update->creator->full_name ?? 'System',
                ];
            }),
        ]);
    }

    /**
     * Build a unified timeline combining activities and transports.
     */
    private function buildTimeline($trip)
    {
        $timeline = collect();

        // Add activities to timeline
        foreach ($trip->activities as $activity) {
            $dateTime = $activity->activity_date . ' ' . $activity->activity_time;
            $timeline->push([
                'type' => 'activity',
                'datetime' => $dateTime,
                'date' => $activity->activity_date,
                'time' => $activity->activity_time,
                'end_time' => $activity->end_time,
                'title' => $activity->activity_type,
                'location' => $activity->location,
                'status' => $activity->status,
                'details' => [
                    'activity_id' => $activity->activity_id,
                ],
            ]);
        }

        // Add transports to timeline
        foreach ($trip->transports as $transport) {
            $dateTime = $transport->departure_time;
            $date = Carbon::parse($transport->departure_time)->toDateString();
            $time = Carbon::parse($transport->departure_time)->toTimeString();

            $timeline->push([
                'type' => 'transport',
                'datetime' => $dateTime,
                'date' => $date,
                'time' => $time,
                'end_time' => $transport->arrival_time ? Carbon::parse($transport->arrival_time)->toTimeString() : null,
                'title' => $transport->transport_type . ': ' . $transport->route_from . ' → ' . $transport->route_to,
                'location' => $transport->route_from,
                'status' => Carbon::parse($transport->departure_time)->isPast() ? 'DONE' : 'SCHEDULED',
                'details' => [
                    'transport_id' => $transport->transport_id,
                    'route_from' => $transport->route_from,
                    'route_to' => $transport->route_to,
                    'driver' => $transport->driver->name ?? null,
                ],
            ]);
        }

        // Sort by datetime
        return $timeline->sortBy('datetime')->values();
    }

    /**
     * Build daily schedule grouped by date.
     */
    private function buildDailySchedule($trip)
    {
        $timeline = $this->buildTimeline($trip);

        // Group by date
        $grouped = $timeline->groupBy('date');

        // Format as array with day info
        $schedule = [];
        $dayNumber = 1;

        foreach ($grouped->sortKeys() as $date => $events) {
            $schedule[] = [
                'day' => $dayNumber,
                'date' => $date,
                'day_name' => Carbon::parse($date)->format('l'),
                'events' => $events->sortBy('time')->values()->map(function ($event) {
                    return [
                        'type' => $event['type'],
                        'time' => $event['time'],
                        'end_time' => $event['end_time'],
                        'title' => $event['title'],
                        'location' => $event['location'],
                        'status' => $event['status'],
                    ];
                }),
            ];
            $dayNumber++;
        }

        return $schedule;
    }

    /**
     * Get the schedule for the current/next day only.
     */
    public function today($trip_id)
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

        // Get trip with activities and transports
        $trip = Trip::with(['activities', 'transports'])->find($trip_id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found.'], 404);
        }

        $today = now()->toDateString();

        // Filter today's activities
        $todayActivities = $trip->activities
            ->where('activity_date', $today)
            ->sortBy('activity_time')
            ->values();

        // Filter today's transports
        $todayTransports = $trip->transports
            ->filter(function ($transport) use ($today) {
                return Carbon::parse($transport->departure_time)->toDateString() == $today;
            })
            ->sortBy('departure_time')
            ->values();

        // Build today's timeline
        $todayTimeline = collect();

        foreach ($todayActivities as $activity) {
            $todayTimeline->push([
                'type' => 'activity',
                'time' => $activity->activity_time,
                'end_time' => $activity->end_time,
                'title' => $activity->activity_type,
                'location' => $activity->location,
                'status' => $activity->status,
            ]);
        }

        foreach ($todayTransports as $transport) {
            $todayTimeline->push([
                'type' => 'transport',
                'time' => Carbon::parse($transport->departure_time)->toTimeString(),
                'end_time' => $transport->arrival_time ? Carbon::parse($transport->arrival_time)->toTimeString() : null,
                'title' => $transport->transport_type . ': ' . $transport->route_from . ' → ' . $transport->route_to,
                'location' => $transport->route_from,
                'status' => Carbon::parse($transport->departure_time)->isPast() ? 'DONE' : 'SCHEDULED',
            ]);
        }

        return response()->json([
            'message' => 'Today\'s schedule retrieved successfully.',
            'trip_id' => $trip->trip_id,
            'trip_name' => $trip->trip_name,
            'date' => $today,
            'day_name' => now()->format('l'),
            'activities_count' => $todayActivities->count(),
            'transports_count' => $todayTransports->count(),
            'schedule' => $todayTimeline->sortBy('time')->values(),
        ]);
    }
}
