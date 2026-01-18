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
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('accommodation_id')->nullable()->constrained('accommodations', 'accommodation_id')->onDelete('set null');
            $table->string('room_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['accommodation_id']);
            $table->dropColumn(['accommodation_id', 'room_type']);
        });
    }
};
