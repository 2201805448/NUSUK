<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    /**
     * Get comprehensive dashboard statistics.
     * Returns real data for Total Pilgrims, Total Supervisors, and other key metrics.
     */
    public function getStats()
    {
        // User Statistics by Role
        $totalPilgrims = User::whereRaw('LOWER(role) = ?', ['pilgrim'])->count();
        $totalSupervisors = User::whereRaw('LOWER(role) = ?', ['supervisor'])->count();
        $totalAdmins = User::whereRaw('LOWER(role) = ?', ['admin'])->count();
        $totalSupport = User::whereRaw('LOWER(role) = ?', ['support'])->count();
        $totalUsers = User::count();

        // Active vs Inactive Users
        $activeUsers = User::where('account_status', 'ACTIVE')->count();
        $inactiveUsers = User::where('account_status', 'INACTIVE')->count();
        $blockedUsers = User::where('account_status', 'BLOCKED')->count();

        // Pilgrim Records (from pilgrims table)
        $pilgrimRecords = Pilgrim::count();

        // Trip Statistics
        $totalTrips = Trip::count();
        $activeTrips = Trip::where('status', 'ACTIVE')->count();
        $completedTrips = Trip::where('status', 'COMPLETED')->count();
        $upcomingTrips = Trip::where('status', 'UPCOMING')->count();

        // Booking Statistics
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'PENDING')->count();
        $confirmedBookings = Booking::where('status', 'CONFIRMED')->count();
        $cancelledBookings = Booking::where('status', 'CANCELLED')->count();

        // Support Tickets (if table exists)
        $totalTickets = 0;
        $openTickets = 0;
        $closedTickets = 0;
        try {
            if (class_exists(Ticket::class)) {
                $totalTickets = Ticket::count();
                $openTickets = Ticket::where('status', 'OPEN')->count();
                $closedTickets = Ticket::where('status', 'CLOSED')->count();
            }
        } catch (\Exception $e) {
            // Tickets table may not exist
        }

        // Recent Activity - New users in last 7 days
        $newUsersThisWeek = User::where('created_at', '>=', now()->subDays(7))->count();
        $newBookingsThisWeek = Booking::where('created_at', '>=', now()->subDays(7))->count();

        return response()->json([
            // Primary Metrics (المعتمرين والمشرفين)
            'total_pilgrims' => $totalPilgrims,           // المعتمرين
            'total_supervisors' => $totalSupervisors,     // المشرفين
            'pilgrim_records' => $pilgrimRecords,         // Pilgrim table records

            // User Breakdown
            'users' => [
                'total' => $totalUsers,
                'admins' => $totalAdmins,
                'supervisors' => $totalSupervisors,
                'pilgrims' => $totalPilgrims,
                'support' => $totalSupport,
                'by_status' => [
                    'active' => $activeUsers,
                    'inactive' => $inactiveUsers,
                    'blocked' => $blockedUsers,
                ],
            ],

            // Trip Metrics
            'trips' => [
                'total' => $totalTrips,
                'active' => $activeTrips,
                'completed' => $completedTrips,
                'upcoming' => $upcomingTrips,
            ],

            // Booking Metrics
            'bookings' => [
                'total' => $totalBookings,
                'pending' => $pendingBookings,
                'confirmed' => $confirmedBookings,
                'cancelled' => $cancelledBookings,
            ],

            // Support Tickets
            'tickets' => [
                'total' => $totalTickets,
                'open' => $openTickets,
                'closed' => $closedTickets,
            ],

            // Recent Activity
            'recent_activity' => [
                'new_users_this_week' => $newUsersThisWeek,
                'new_bookings_this_week' => $newBookingsThisWeek,
            ],
        ]);
    }
}
