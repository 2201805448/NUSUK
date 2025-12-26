<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Booking;
use App\Models\Trip;

class AdminController extends Controller
{
    // 1. Dashboard Stats
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_pilgrims' => Pilgrim::count(),
            'total_bookings' => Booking::count(),
            'total_trips' => Trip::count(),
            'active_users' => User::where('account_status', 'ACTIVE')->count(),
            'pending_users' => User::where('account_status', 'INACTIVE')->count(),
        ]);
    }

    // 2. List Users (Paginated & Filterable)
    public function users(Request $request)
    {
        $query = User::query();

        // Filter by Role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by Status
        if ($request->has('status')) {
            $query->where('account_status', $request->status);
        }

        // Search by Name or Email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(10));
    }

    // 3. Store User
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:users',
            'phone_number' => 'required|string|max:30',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMIN,USER,SUPERVISOR,SUPPORT,PILGRIM',
            'account_status' => 'required|in:ACTIVE,INACTIVE,BLOCKED'
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => $request->role,
            'account_status' => $request->account_status,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    // 4. Show User
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // 5. Update User
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'email' => 'sometimes|string|email|max:150|unique:users,email,' . $id . ',user_id',
            'phone_number' => 'sometimes|string|max:30',
            'role' => 'sometimes|in:ADMIN,USER,SUPERVISOR,SUPPORT,PILGRIM',
            'account_status' => 'sometimes|in:ACTIVE,INACTIVE,BLOCKED',
            'password' => 'sometimes|string|min:8'
        ]);

        $userData = $request->except(['password']);

        if ($request->filled('password')) {
            $userData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->update($userData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    // 6. Delete User
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    // 7. Update User Status (Block/Activate) - Kept for specific convenience
    public function updateUserStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:ACTIVE,INACTIVE,BLOCKED'
        ]);

        $user = User::findOrFail($id);
        $user->account_status = $request->status;
        $user->save();

        return response()->json([
            'message' => "User status updated to {$request->status}",
            'user' => $user
        ]);
    }
}
