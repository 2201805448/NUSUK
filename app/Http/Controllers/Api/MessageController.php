<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\GroupMember;
use App\Models\GroupTrip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Get list of users the authenticated user can message.
     * - Pilgrim: Can only see their Supervisor(s) and Admins
     * - Supervisor: Can only see their assigned Pilgrims and Admins
     * - Admin: Can see all users
     */
    public function getContactableUsers()
    {
        $user = Auth::user();
        $role = strtolower($user->role);

        if ($role === 'admin') {
            // Admin can see all users except themselves
            $users = User::where('user_id', '!=', $user->user_id)
                ->select('user_id', 'full_name', 'role', 'email')
                ->get();
        } elseif ($role === 'supervisor') {
            // Supervisor can see:
            // 1. All pilgrims in their groups (linked via groups_trips where supervisor_id = this user)
            // 2. All admins

            // Get pilgrim user IDs from groups supervised by this user
            $pilgrimUserIds = DB::table('group_members')
                ->join('groups_trips', 'group_members.group_id', '=', 'groups_trips.group_id')
                ->join('pilgrims', 'group_members.pilgrim_id', '=', 'pilgrims.pilgrim_id')
                ->where('groups_trips.supervisor_id', $user->user_id)
                ->pluck('pilgrims.user_id')
                ->toArray();

            // DEBUG: Log for troubleshooting
            \Log::info('MessageController - Supervisor contacts lookup', [
                'supervisor_user_id' => $user->user_id,
                'found_pilgrim_user_ids' => $pilgrimUserIds,
            ]);

            // Get pilgrims as users
            $pilgrimUsers = User::whereIn('user_id', $pilgrimUserIds)
                ->select('user_id', 'full_name', 'role', 'email')
                ->get();

            // Get ALL admin users separately (always include)
            $adminUsers = User::where('role', 'Admin')
                ->select('user_id', 'full_name', 'role', 'email')
                ->get();

            \Log::info('MessageController - Supervisor: Admin users found', [
                'admin_count' => $adminUsers->count(),
                'admin_ids' => $adminUsers->pluck('user_id')->toArray(),
            ]);

            // Merge pilgrims and admins, remove duplicates
            $users = $pilgrimUsers->merge($adminUsers)->unique('user_id')->values();
        } elseif ($role === 'pilgrim') {
            // Pilgrim can see:
            // 1. Their supervisor(s) from their group(s)
            // 2. All admins

            // Get pilgrim record for this user
            $pilgrim = $user->pilgrim;

            $supervisorIds = [];
            if ($pilgrim) {
                // Get supervisor IDs from groups this pilgrim belongs to
                $supervisorIds = DB::table('group_members')
                    ->join('groups_trips', 'group_members.group_id', '=', 'groups_trips.group_id')
                    ->where('group_members.pilgrim_id', $pilgrim->pilgrim_id)
                    ->whereNotNull('groups_trips.supervisor_id')
                    ->pluck('groups_trips.supervisor_id')
                    ->toArray();
            }

            // Get supervisors as users
            $supervisorUsers = User::whereIn('user_id', $supervisorIds)
                ->select('user_id', 'full_name', 'role', 'email')
                ->get();

            // Get ALL admin users separately (always include)
            $adminUsers = User::where('role', 'Admin')
                ->select('user_id', 'full_name', 'role', 'email')
                ->get();

            \Log::info('MessageController - Pilgrim: Admin users found', [
                'admin_count' => $adminUsers->count(),
                'admin_ids' => $adminUsers->pluck('user_id')->toArray(),
            ]);

            // Merge supervisors and admins, remove duplicates
            $users = $supervisorUsers->merge($adminUsers)->unique('user_id')->values();
        } else {
            // Default: no contacts (Support role or other roles)
            $users = collect([]);
        }

        return response()->json($users);
    }

    /**
     * Send a message to another user.
     * Validates that the receiver is in the allowed contacts list.
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'content' => 'required|string',
        ]);

        $sender = Auth::user();
        $receiverId = $request->receiver_id;

        // Validate that sender can message this receiver
        if (!$this->canMessageUser($sender, $receiverId)) {
            return response()->json([
                'message' => 'غير مسموح لك بمراسلة هذا المستخدم'
            ], 403);
        }

        $message = Message::create([
            'sender_id' => $sender->user_id,
            'receiver_id' => $receiverId,
            'content' => $request->content,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }

    /**
     * Get list of latest conversations (filtered by allowed contacts).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        // Get allowed user IDs for this user
        $allowedUserIds = $this->getAllowedUserIds($user);

        $conversations = Message::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->where(function ($q) use ($userId, $allowedUserIds) {
                // Filter to only show conversations with allowed users
                $q->where(function ($inner) use ($userId, $allowedUserIds) {
                    $inner->where('sender_id', $userId)
                        ->whereIn('receiver_id', $allowedUserIds);
                })->orWhere(function ($inner) use ($userId, $allowedUserIds) {
                    $inner->whereIn('sender_id', $allowedUserIds)
                        ->where('receiver_id', $userId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique(function ($item) use ($userId) {
                return $item->sender_id == $userId ? $item->receiver_id : $item->sender_id;
            })
            ->values();

        $conversations->transform(function ($msg) use ($userId) {
            $otherUserId = $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
            $otherUser = User::find($otherUserId);

            return [
                'user_id' => $otherUserId,
                'user_name' => $otherUser ? $otherUser->getAttribute('full_name') : 'Unknown',
                'user_role' => $otherUser ? $otherUser->getAttribute('role') : 'Unknown',
                'last_message' => $msg->content,
                'timestamp' => $msg->created_at,
            ];
        });

        return response()->json($conversations);
    }

    /**
     * Get conversation with a specific user.
     * Validates that the user is in allowed contacts.
     */
    public function show(Request $request, $user_id)
    {
        $authUser = Auth::user();
        $authId = $authUser->user_id;

        // Validate that user can view this conversation
        if (!$this->canMessageUser($authUser, $user_id)) {
            return response()->json([
                'message' => 'غير مسموح لك بعرض هذه المحادثة'
            ], 403);
        }

        $messages = Message::where(function ($q) use ($authId, $user_id) {
            $q->where('sender_id', $authId)->where('receiver_id', $user_id);
        })
            ->orWhere(function ($q) use ($authId, $user_id) {
                $q->where('sender_id', $user_id)->where('receiver_id', $authId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Check if a user can message another user based on role restrictions.
     */
    private function canMessageUser($sender, $receiverId)
    {
        // Allow if receiver_id is 0, null, or empty (loading chat dashboard before selecting contact)
        // Cast to int first to handle string "0" from URL
        $receiverIdInt = (int) $receiverId;

        if ($receiverIdInt === 0 || $receiverId === null || $receiverId === '') {
            \Log::info('MessageController - canMessageUser: Allowing access (receiver_id is 0/null/empty)', [
                'sender_id' => $sender->user_id,
                'receiver_id_raw' => $receiverId,
                'receiver_id_int' => $receiverIdInt,
            ]);
            return true;
        }

        $allowedUserIds = $this->getAllowedUserIds($sender);

        // DEBUG: Log for troubleshooting authorization issues
        \Log::info('MessageController - canMessageUser check', [
            'sender_id' => $sender->user_id,
            'sender_role' => $sender->role,
            'receiver_id' => $receiverIdInt,
            'allowed_user_ids' => $allowedUserIds,
            'is_allowed' => in_array($receiverIdInt, $allowedUserIds),
        ]);

        return in_array($receiverIdInt, $allowedUserIds);
    }

    /**
     * Get array of user IDs that the given user can communicate with.
     */
    private function getAllowedUserIds($user)
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            // Admin can message anyone
            return User::where('user_id', '!=', $user->user_id)
                ->pluck('user_id')
                ->toArray();
        } elseif ($role === 'supervisor') {
            // Supervisor: pilgrims in their groups + admins
            $pilgrimUserIds = DB::table('group_members')
                ->join('groups_trips', 'group_members.group_id', '=', 'groups_trips.group_id')
                ->join('pilgrims', 'group_members.pilgrim_id', '=', 'pilgrims.pilgrim_id')
                ->where('groups_trips.supervisor_id', $user->user_id)
                ->pluck('pilgrims.user_id')
                ->toArray();

            // DEBUG: Log for troubleshooting
            \Log::info('MessageController - getAllowedUserIds for Supervisor', [
                'supervisor_user_id' => $user->user_id,
                'found_pilgrim_user_ids' => $pilgrimUserIds,
            ]);

            // Get ALL admin user IDs (exact match 'Admin')
            $adminIds = User::where('role', 'Admin')
                ->pluck('user_id')
                ->toArray();

            return array_unique(array_merge($pilgrimUserIds, $adminIds));
        } elseif ($role === 'pilgrim') {
            // Pilgrim: their supervisors + admins
            $pilgrim = $user->pilgrim;
            $supervisorIds = [];

            if ($pilgrim) {
                $supervisorIds = DB::table('group_members')
                    ->join('groups_trips', 'group_members.group_id', '=', 'groups_trips.group_id')
                    ->where('group_members.pilgrim_id', $pilgrim->pilgrim_id)
                    ->whereNotNull('groups_trips.supervisor_id')
                    ->pluck('groups_trips.supervisor_id')
                    ->toArray();
            }

            // Get ALL admin user IDs (exact match 'Admin')
            $adminIds = User::where('role', 'Admin')
                ->pluck('user_id')
                ->toArray();

            return array_unique(array_merge($supervisorIds, $adminIds));
        }

        return [];
    }
}
