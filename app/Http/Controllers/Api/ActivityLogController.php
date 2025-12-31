<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pilgrim;
use App\Models\GroupMember;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * View the log of trips and activities the pilgrim has participated in.
     * Includes programs and visits associated with each trip.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json([
                'message' => 'Pilgrim profile not found.',
                'data' => []
            ], 404);
        }

        // Get all group memberships for this pilgrim
        $groupMemberships = GroupMember::with('groupTrip.trip.activities', 'groupTrip.trip.package')
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->get();

        // Extract unique trips
        $tripIds = $groupMemberships->pluck('groupTrip.trip_id')->filter()->unique();

        // Get full trip details with activities
        $trips = Trip::with(['activities', 'package', 'accommodations'])
            ->whereIn('trip_id', $tripIds)
            ->orderBy('start_date', 'desc')
            ->get();

        // Categorize trips
        $pastTrips = $trips->filter(function ($trip) {
            return $trip->end_date && $trip->end_date < now();
        });

        $currentTrips = $trips->filter(function ($trip) {
            return $trip->start_date <= now() && (!$trip->end_date || $trip->end_date >= now());
        });

        $upcomingTrips = $trips->filter(function ($trip) {
            return $trip->start_date > now();
        });

        // Format the activity log
        $activityLog = $trips->map(function ($trip) use ($groupMemberships) {
            // Find the group membership for this trip
            $membership = $groupMemberships->first(function ($m) use ($trip) {
                return $m->groupTrip && $m->groupTrip->trip_id == $trip->trip_id;
            });

            // Categorize activities
            $activities = $trip->activities->map(function ($activity) {
                return [
                    'activity_id' => $activity->activity_id,
                    'activity_type' => $activity->activity_type,
                    'location' => $activity->location,
                    'activity_date' => $activity->activity_date,
                    'activity_time' => $activity->activity_time,
                    'end_time' => $activity->end_time ?? null,
                    'status' => $activity->status,
                ];
            })->sortBy('activity_date')->values();

            // Determine trip status category
            $statusCategory = 'upcoming';
            if ($trip->end_date && $trip->end_date < now()) {
                $statusCategory = 'past';
            } elseif ($trip->start_date <= now()) {
                $statusCategory = 'current';
            }

            return [
                'trip_id' => $trip->trip_id,
                'trip_name' => $trip->trip_name,
                'start_date' => $trip->start_date,
                'end_date' => $trip->end_date,
                'trip_status' => $trip->status,
                'status_category' => $statusCategory,
                'package' => $trip->package ? [
                    'package_id' => $trip->package->package_id,
                    'package_name' => $trip->package->package_name ?? null,
                    'duration_days' => $trip->package->duration_days ?? null,
                ] : null,
                'group_info' => $membership && $membership->groupTrip ? [
                    'group_id' => $membership->groupTrip->group_id,
                    'group_code' => $membership->groupTrip->group_code,
                    'join_date' => $membership->join_date,
                    'member_status' => $membership->member_status,
                ] : null,
                'hotels' => $trip->accommodations->map(function ($hotel) {
                    return [
                        'accommodation_id' => $hotel->accommodation_id,
                        'hotel_name' => $hotel->hotel_name ?? null,
                        'location' => $hotel->location ?? null,
                    ];
                }),
                'activities_count' => $activities->count(),
                'completed_activities' => $activities->where('status', 'DONE')->count(),
                'activities' => $activities,
            ];
        });

        return response()->json([
            'message' => 'Activity log retrieved successfully.',
            'summary' => [
                'total_trips' => $trips->count(),
                'past_trips' => $pastTrips->count(),
                'current_trips' => $currentTrips->count(),
                'upcoming_trips' => $upcomingTrips->count(),
                'total_activities' => $trips->sum(function ($trip) {
                    return $trip->activities->count();
                }),
                'completed_activities' => $trips->sum(function ($trip) {
                    return $trip->activities->where('status', 'DONE')->count();
                }),
            ],
            'activity_log' => $activityLog
        ]);
    }

    /**
     * View detailed activity log for a specific trip.
     */
    public function show($trip_id)
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Verify the pilgrim was part of this trip
        $membership = GroupMember::with('groupTrip')
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->whereHas('groupTrip', function ($q) use ($trip_id) {
                $q->where('trip_id', $trip_id);
            })
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'You did not participate in this trip.'], 403);
        }

        // Get full trip details
        $trip = Trip::with(['activities', 'package', 'accommodations', 'transports'])
            ->find($trip_id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found.'], 404);
        }

        // Organize activities by date
        $activitiesByDate = $trip->activities
            ->groupBy('activity_date')
            ->map(function ($dayActivities, $date) {
                return [
                    'date' => $date,
                    'activities' => $dayActivities->map(function ($activity) {
                        return [
                            'activity_id' => $activity->activity_id,
                            'activity_type' => $activity->activity_type,
                            'location' => $activity->location,
                            'activity_time' => $activity->activity_time,
                            'end_time' => $activity->end_time ?? null,
                            'status' => $activity->status,
                        ];
                    })->sortBy('activity_time')->values()
                ];
            })->sortKeys()->values();

        return response()->json([
            'message' => 'Trip activity log retrieved successfully.',
            'trip' => [
                'trip_id' => $trip->trip_id,
                'trip_name' => $trip->trip_name,
                'start_date' => $trip->start_date,
                'end_date' => $trip->end_date,
                'trip_status' => $trip->status,
                'notes' => $trip->notes,
                'package' => $trip->package ? [
                    'package_id' => $trip->package->package_id,
                    'package_name' => $trip->package->package_name ?? null,
                    'duration_days' => $trip->package->duration_days ?? null,
                    'services' => $trip->package->services ?? null,
                ] : null,
                'group_info' => [
                    'group_id' => $membership->groupTrip->group_id,
                    'group_code' => $membership->groupTrip->group_code,
                    'join_date' => $membership->join_date,
                    'member_status' => $membership->member_status,
                ],
                'hotels' => $trip->accommodations->map(function ($hotel) {
                    return [
                        'accommodation_id' => $hotel->accommodation_id,
                        'hotel_name' => $hotel->hotel_name ?? null,
                        'location' => $hotel->location ?? null,
                        'star_rating' => $hotel->star_rating ?? null,
                    ];
                }),
                'transports' => $trip->transports->map(function ($transport) {
                    return [
                        'transport_id' => $transport->transport_id ?? null,
                        'vehicle_type' => $transport->vehicle_type ?? null,
                        'vehicle_number' => $transport->vehicle_number ?? null,
                    ];
                }),
            ],
            'activities_summary' => [
                'total' => $trip->activities->count(),
                'completed' => $trip->activities->where('status', 'DONE')->count(),
                'scheduled' => $trip->activities->where('status', 'SCHEDULED')->count(),
                'cancelled' => $trip->activities->where('status', 'CANCELLED')->count(),
            ],
            'program_by_date' => $activitiesByDate,
        ]);
    }
}
