<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:users',
            'phone_number' => 'required|string|max:30',
            'password' => 'required|string|confirmed|min:8',
            'role' => 'required|in:ADMIN,USER,SUPERVISOR,SUPPORT',
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'account_status' => 'ACTIVE',
        ]);

        Auth::login($user);

        return redirect('/')->with('success', 'Account created successfully!');
    }
}
