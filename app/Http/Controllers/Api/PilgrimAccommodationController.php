<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pilgrim;
use App\Models\RoomAssignment;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PilgrimAccommodationController extends Controller
{
    /**
     * View all accommodation details for the authenticated pilgrim.
     * Includes hotel name, city, room type, and length of stay.
     */
    public function index()
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Get all room assignments for this pilgrim
        $assignments = RoomAssignment::with(['accommodation', 'room'])
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->orderBy('check_in', 'desc')
            ->get();

        if ($assignments->isEmpty()) {
            return response()->json([
                'message' => 'No accommodation assignments found.',
                'data' => []
            ]);
        }

        // Categorize accommodations
        $currentAccommodation = $assignments->filter(function ($a) {
            return $a->check_in <= now() && $a->check_out >= now() && $a->status !== 'CANCELLED';
        })->first();

        $upcomingAccommodations = $assignments->filter(function ($a) {
            return $a->check_in > now() && $a->status !== 'CANCELLED';
        });

        $pastAccommodations = $assignments->filter(function ($a) {
            return $a->check_out < now();
        });

        // Format the response
        $formattedAccommodations = $assignments->map(function ($assignment) {
            $checkIn = Carbon::parse($assignment->check_in);
            $checkOut = Carbon::parse($assignment->check_out);
            $stayDuration = $checkIn->diffInDays($checkOut);

            // Determine status category
            $statusCategory = 'past';
            if ($assignment->check_in <= now() && $assignment->check_out >= now()) {
                $statusCategory = 'current';
            } elseif ($assignment->check_in > now()) {
                $statusCategory = 'upcoming';
            }

            return [
                'assignment_id' => $assignment->assignment_id,
                'status' => $assignment->status,
                'status_category' => $statusCategory,
                'hotel' => $assignment->accommodation ? [
                    'accommodation_id' => $assignment->accommodation->accommodation_id,
                    'hotel_name' => $assignment->accommodation->hotel_name,
                    'city' => $assignment->accommodation->city,
                    'room_type' => $assignment->accommodation->room_type,
                    'capacity' => $assignment->accommodation->capacity,
                    'notes' => $assignment->accommodation->notes,
                    'stars' => $assignment->accommodation->start,
                    'phone' => $assignment->accommodation->phone,
                    'email' => $assignment->accommodation->email,
                ] : null,
                'room' => $assignment->room ? [
                    'room_id' => $assignment->room->id,
                    'room_number' => $assignment->room->room_number,
                    'floor' => $assignment->room->floor,
                    'room_type' => $assignment->room->room_type,
                ] : null,
                'stay' => [
                    'check_in' => $assignment->check_in,
                    'check_out' => $assignment->check_out,
                    'duration_nights' => $stayDuration,
                    'check_in_day' => $checkIn->format('l'),
                    'check_out_day' => $checkOut->format('l'),
                ],
            ];
        });

        return response()->json([
            'message' => 'Accommodation details retrieved successfully.',
            'summary' => [
                'total_accommodations' => $assignments->count(),
                'current' => $currentAccommodation ? 1 : 0,
                'upcoming' => $upcomingAccommodations->count(),
                'past' => $pastAccommodations->count(),
            ],
            'current_accommodation' => $currentAccommodation ? $formattedAccommodations->firstWhere('assignment_id', $currentAccommodation->assignment_id) : null,
            'accommodations' => $formattedAccommodations,
        ]);
    }

    /**
     * View accommodation details for a specific trip.
     */
    public function forTrip($trip_id)
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Verify the pilgrim is part of this trip
        $membership = GroupMember::with('groupTrip.trip.accommodations')
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->whereHas('groupTrip', function ($q) use ($trip_id) {
                $q->where('trip_id', $trip_id);
            })
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'You are not registered for this trip.'], 403);
        }

        $trip = $membership->groupTrip->trip;

        // Get pilgrim's room assignments for accommodations in this trip
        $tripAccommodationIds = $trip->accommodations->pluck('accommodation_id');

        $assignments = RoomAssignment::with(['accommodation', 'room'])
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->whereIn('accommodation_id', $tripAccommodationIds)
            ->orderBy('check_in', 'asc')
            ->get();

        // Get all trip accommodations (including those pilgrim may not be assigned to yet)
        $tripAccommodations = $trip->accommodations->map(function ($hotel) use ($assignments, $pilgrim) {
            $assignment = $assignments->firstWhere('accommodation_id', $hotel->accommodation_id);

            return [
                'accommodation_id' => $hotel->accommodation_id,
                'hotel_name' => $hotel->hotel_name,
                'city' => $hotel->city,
                'room_type' => $hotel->room_type,
                'capacity' => $hotel->capacity,
                'notes' => $hotel->notes,
                'stars' => $hotel->start,
                'phone' => $hotel->phone,
                'email' => $hotel->email,
                'your_assignment' => $assignment ? [
                    'assignment_id' => $assignment->assignment_id,
                    'status' => $assignment->status,
                    'room' => $assignment->room ? [
                        'room_number' => $assignment->room->room_number,
                        'floor' => $assignment->room->floor,
                        'room_type' => $assignment->room->room_type,
                    ] : null,
                    'check_in' => $assignment->check_in,
                    'check_out' => $assignment->check_out,
                    'duration_nights' => Carbon::parse($assignment->check_in)->diffInDays(Carbon::parse($assignment->check_out)),
                ] : null,
            ];
        });

        return response()->json([
            'message' => 'Trip accommodation details retrieved successfully.',
            'trip' => [
                'trip_id' => $trip->trip_id,
                'trip_name' => $trip->trip_name,
                'start_date' => $trip->start_date,
                'end_date' => $trip->end_date,
            ],
            'accommodations_count' => $tripAccommodations->count(),
            'assigned_count' => $assignments->count(),
            'accommodations' => $tripAccommodations,
        ]);
    }

    /**
     * View current accommodation details only.
     */
    public function current()
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Find current active accommodation
        $currentAssignment = RoomAssignment::with(['accommodation', 'room'])
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->where('check_in', '<=', now())
            ->where('check_out', '>=', now())
            ->whereIn('status', ['CONFIRMED', 'PENDING'])
            ->first();

        if (!$currentAssignment) {
            return response()->json([
                'message' => 'No current accommodation found.',
                'data' => null
            ]);
        }

        $checkIn = Carbon::parse($currentAssignment->check_in);
        $checkOut = Carbon::parse($currentAssignment->check_out);
        $daysRemaining = now()->diffInDays($checkOut, false);

        return response()->json([
            'message' => 'Current accommodation retrieved successfully.',
            'accommodation' => [
                'assignment_id' => $currentAssignment->assignment_id,
                'status' => $currentAssignment->status,
                'hotel' => $currentAssignment->accommodation ? [
                    'accommodation_id' => $currentAssignment->accommodation->accommodation_id,
                    'hotel_name' => $currentAssignment->accommodation->hotel_name,
                    'city' => $currentAssignment->accommodation->city,
                    'room_type' => $currentAssignment->accommodation->room_type,
                    'capacity' => $currentAssignment->accommodation->capacity,
                    'notes' => $currentAssignment->accommodation->notes,
                    'stars' => $currentAssignment->accommodation->start,
                    'phone' => $currentAssignment->accommodation->phone,
                    'email' => $currentAssignment->accommodation->email,
                ] : null,
                'room' => $currentAssignment->room ? [
                    'room_number' => $currentAssignment->room->room_number,
                    'floor' => $currentAssignment->room->floor,
                    'room_type' => $currentAssignment->room->room_type,
                ] : null,
                'stay' => [
                    'check_in' => $currentAssignment->check_in,
                    'check_out' => $currentAssignment->check_out,
                    'duration_nights' => $checkIn->diffInDays($checkOut),
                    'days_remaining' => max(0, $daysRemaining),
                    'check_out_day' => $checkOut->format('l, F j'),
                ],
            ],
        ]);
    }

    /**
     * View housing data within the trip context.
     * Includes place of residence, room number, check-in/out dates, and group association.
     */
    public function housing($trip_id)
    {
        $user = Auth::user();

        // Find the pilgrim profile
        $pilgrim = Pilgrim::where('user_id', $user->user_id)->first();

        if (!$pilgrim) {
            return response()->json(['message' => 'Pilgrim profile not found.'], 404);
        }

        // Get the group membership with supervisor info
        $membership = GroupMember::with(['groupTrip.trip.accommodations', 'groupTrip.supervisor'])
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->whereHas('groupTrip', function ($q) use ($trip_id) {
                $q->where('trip_id', $trip_id);
            })
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'You are not registered for this trip.'], 403);
        }

        $trip = $membership->groupTrip->trip;
        $group = $membership->groupTrip;

        // Get room assignments for this trip's accommodations
        $tripAccommodationIds = $trip->accommodations->pluck('accommodation_id');

        $assignments = RoomAssignment::with(['accommodation', 'room'])
            ->where('pilgrim_id', $pilgrim->pilgrim_id)
            ->whereIn('accommodation_id', $tripAccommodationIds)
            ->orderBy('check_in', 'asc')
            ->get();

        // Format housing data
        $housingData = $assignments->map(function ($assignment) {
            $checkIn = Carbon::parse($assignment->check_in);
            $checkOut = Carbon::parse($assignment->check_out);

            // Determine current status
            $isCurrent = $assignment->check_in <= now() && $assignment->check_out >= now();
            $isUpcoming = $assignment->check_in > now();
            $isPast = $assignment->check_out < now();

            return [
                'assignment_id' => $assignment->assignment_id,
                'status' => $assignment->status,
                'is_current' => $isCurrent,
                'is_upcoming' => $isUpcoming,
                'is_past' => $isPast,
                'place_of_residence' => [
                    'hotel_name' => $assignment->accommodation->hotel_name ?? null,
                    'city' => $assignment->accommodation->city ?? null,
                    'address' => $assignment->accommodation->notes ?? null,
                    'room_type' => $assignment->accommodation->room_type ?? null,
                    'capacity' => $assignment->accommodation->capacity ?? null,
                    'stars' => $assignment->accommodation->start ?? null,
                    'phone' => $assignment->accommodation->phone ?? null,
                    'email' => $assignment->accommodation->email ?? null,
                ],
                'room' => [
                    'room_number' => $assignment->room->room_number ?? null,
                    'floor' => $assignment->room->floor ?? null,
                    'room_type' => $assignment->room->room_type ?? null,
                ],
                'dates' => [
                    'check_in' => $assignment->check_in,
                    'check_out' => $assignment->check_out,
                    'check_in_formatted' => $checkIn->format('l, F j, Y'),
                    'check_out_formatted' => $checkOut->format('l, F j, Y'),
                    'duration_nights' => $checkIn->diffInDays($checkOut),
                ],
            ];
        });

        // Get current housing
        $currentHousing = $housingData->firstWhere('is_current', true);

        return response()->json([
            'message' => 'Housing data retrieved successfully.',
            'trip' => [
                'trip_id' => $trip->trip_id,
                'trip_name' => $trip->trip_name,
                'start_date' => $trip->start_date,
                'end_date' => $trip->end_date,
                'trip_status' => $trip->status,
            ],
            'group' => [
                'group_id' => $group->group_id,
                'group_code' => $group->group_code,
                'group_status' => $group->group_status,
                'supervisor' => $group->supervisor ? [
                    'name' => $group->supervisor->full_name,
                    'phone' => $group->supervisor->phone_number,
                    'email' => $group->supervisor->email,
                ] : null,
                'join_date' => $membership->join_date,
                'member_status' => $membership->member_status,
            ],
            'housing_summary' => [
                'total_assignments' => $housingData->count(),
                'current' => $housingData->where('is_current', true)->count(),
                'upcoming' => $housingData->where('is_upcoming', true)->count(),
                'past' => $housingData->where('is_past', true)->count(),
            ],
            'current_housing' => $currentHousing,
            'all_housing' => $housingData,
        ]);
    }
}
