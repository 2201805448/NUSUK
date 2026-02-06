<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trip_accommodations', function (Blueprint $table) {
            $table->date('check_in')->nullable()->after('accommodation_id');
            $table->date('check_out')->nullable()->after('check_in');
            $table->json('rooms')->nullable()->after('check_out'); // Stores room types/quantities
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_accommodations', function (Blueprint $table) {
            $table->dropColumn(['check_in', 'check_out', 'rooms']);
        });
    }
};
