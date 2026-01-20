<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all users with role 'USER' (case-insensitive) to 'Pilgrim'
        // Using raw SQL for efficiency and to bypass model events/mutators if any,
        // though strictly we want the string 'Pilgrim'.
        DB::table('users')
            ->where(DB::raw('LOWER(role)'), 'user')
            ->update(['role' => 'Pilgrim']);

        // Also ensure any variations like 'pilgrim' are Title Cased if that's the standard
        DB::table('users')
            ->where('role', 'pilgrim')
            ->update(['role' => 'Pilgrim']);

        DB::table('users')
            ->where('role', 'PILGRIM')
            ->update(['role' => 'Pilgrim']);

        // Backfill missing Pilgrim records
        $pilgrimUsers = User::where('role', 'Pilgrim')->get();

        foreach ($pilgrimUsers as $user) {
            // Check if pilgrim record exists
            $exists = DB::table('pilgrims')->where('user_id', $user->user_id)->exists();

            if (!$exists) {
                DB::table('pilgrims')->insert([
                    'user_id' => $user->user_id,
                    'passport_name' => $user->full_name, // Use full name as passport name fallback
                    'passport_number' => 'PENDING',    // Placeholder
                    'nationality' => 'Unknown',        // Placeholder
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting 'Pilgrim' to 'USER' is ambiguous because 'Pilgrim' might have been a valid role before.
        // We will assume that strictly for the purpose of reversal, we might not want to destructively revert users 
        // who were legitimately pilgrims.
        // However, if we must revert to the state before, it's safer to leave them as Pilgrim 
        // or effectively do nothing as 'Pilgrim' is a valid role now.
        // But to satisfy the requirement of "reverting":
        // usage: DB::table('users')->where('role', 'Pilgrim')->update(['role' => 'USER']);
        // I will leave this empty or commented as it's a data cleanup migration.
    }
};
