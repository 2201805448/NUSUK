<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    // 1. Send Reset Code
    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $otp = rand(100000, 999999); // 6-digit OTP

        // Cleanup old OTPs for this email
        DB::table('password_reset_otps')->where('email', $request->email)->delete();

        // Store new OTP
        DB::table('password_reset_otps')->insert([
            'email' => $request->email,
            'otp' => $otp,
            'created_at' => Carbon::now()
        ]);

        // In a real app, send via Email/SMS. For now, we return it or log it.
        // We will output it in the response for testing purposes (NOT SECURE FOR PRODUCTION)
        // or just message that it was sent.

        return response()->json([
            'message' => 'Reset code sent successfully.',
            'debug_otp' => $otp // REMOVE IN PRODUCTION
        ]);
    }

    // 2. Verify Reset Code
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string'
        ]);

        $record = DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        // Check expiry (e.g., 15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['message' => 'OTP expired.'], 400);
        }

        return response()->json(['message' => 'OTP verified successfully.']);
    }

    // 3. Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        // Verify OTP again to ensure security
        $record = DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record || Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }

        // Update Password
        $user = User::where('email', $request->email)->first();
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        // Delete OTP
        DB::table('password_reset_otps')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
