<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    /**
     * List user's tickets.
     */
    public function index()
    {
        $user = Auth::user();

        // استخدام getAttribute لضمان الوصول للمعرف حتى لو كان protected
        $userId = $user->getAttribute('user_id');

        $tickets = Ticket::where('user_id', $userId)
            ->with('logs')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    /**
     * Create a new support ticket.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'trip_id' => 'nullable|exists:trips,trip_id',
            'priority' => 'in:LOW,MEDIUM,HIGH',
        ]);

        // Create Ticket
        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'trip_id' => $request->trip_id,
            'priority' => $request->priority ?? 'LOW',
            'status' => 'OPEN',
        ]);

        // Create Initial Log (Description)
        TicketLog::create([
            'ticket_id' => $ticket->ticket_id,
            'action_by' => Auth::id(),
            'action_note' => $request->description,
        ]);

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => $ticket->load('logs')
        ], 201);
    }

    /**
     * Show detailed ticket info.
     */
    public function show($id)
    {
        $ticket = Ticket::with(['logs.actionBy:user_id,full_name,role'])
            ->findOrFail($id);

        // Security check: only owner or Admin can view
        if (Auth::user()->role !== 'ADMIN' && $ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this ticket.'], 403);
        }

        return response()->json($ticket);
    }

    /**
     * Reply to a ticket (add comment).
     */
    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        if (Auth::user()->role !== 'ADMIN' && $ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this ticket.'], 403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $log = TicketLog::create([
            'ticket_id' => $ticket->ticket_id,
            'action_by' => Auth::id(),
            'action_note' => $request->input('content'),
        ]);

        // Notify User if reply is from someone else
        if ($ticket->user_id !== Auth::id()) {
            try {
                Notification::create([
                    'user_id' => $ticket->user_id,
                    'title' => 'New Reply to Ticket',
                    'message' => 'You have a new reply on your ticket: ' . $ticket->title,
                    'is_read' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Exception $e) {
                // Log error or just ignore for now to prevent crash, but returning it is better for debug
                return response()->json(['message' => 'Reply added but notification failed: ' . $e->getMessage(), 'log' => $log], 201);
            }
        }

        return response()->json([
            'message' => 'Reply added successfully',
            'log' => $log
        ], 201);
    }

    /**
     * Transfer ticket to a department (Admin Only).
     */
    public function transfer(Request $request, $id)
    {
        if (Auth::user()->role !== 'ADMIN') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'department' => 'required|string|max:100',
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update(['department' => $request->department]);

        $log = TicketLog::create([
            'ticket_id' => $ticket->ticket_id,
            'action_by' => Auth::id(),
            'action_note' => "Transferred to department: " . $request->department,
        ]);

        return response()->json([
            'message' => 'Ticket transferred successfully',
            'ticket' => $ticket,
            'log' => $log
        ]);
    }

    /**
     * Close the ticket (Admin Only).
     */
    public function close(Request $request, $id)
    {
        if (Auth::user()->role !== 'ADMIN') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket = Ticket::findOrFail($id);
        $ticket->update(['status' => 'CLOSED']);

        $log = TicketLog::create([
            'ticket_id' => $ticket->ticket_id,
            'action_by' => Auth::id(),
            'action_note' => "Ticket Closed",
        ]);

        // Notify User
        try {
            Notification::create([
                'user_id' => $ticket->user_id,
                'title' => 'Ticket Closed',
                'message' => 'Your ticket has been closed: ' . $ticket->title,
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Ignore or log
        }

        return response()->json([
            'message' => 'Ticket closed successfully',
            'ticket' => $ticket,
            'log' => $log
        ]);
    }
}
