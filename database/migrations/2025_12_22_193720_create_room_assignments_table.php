<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_assignments', function (Blueprint $table) {
            $table->id('assignment_id');

            $table->unsignedBigInteger('pilgrim_id');
            $table->unsignedBigInteger('accommodation_id');

            $table->dateTime('check_in');
            $table->dateTime('check_out');

            $table->enum('status', [
                'CONFIRMED',
                'PENDING',
                'FINISHED',
            ])->default('CONFIRMED');

            // FK: pilgrim_id → pilgrims(pilgrim_id)
            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('cascade');

            // FK: accommodation_id → accommodations(accommodation_id)
            $table->foreign('accommodation_id')
                ->references('accommodation_id')
                ->on('accommodations')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
    }
};