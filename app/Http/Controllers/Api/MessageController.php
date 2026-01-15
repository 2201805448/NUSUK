<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Send a message to another user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'content' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->context,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }

    /**
     * Get list of latest conversations.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
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
                // استخدام getAttribute يحل مشكلة الأخطاء الحمراء في الصور
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
     */
    public function show(Request $request, $user_id)
    {
        $authId = Auth::id();

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
}
