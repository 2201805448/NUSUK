<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_tracking', function (Blueprint $table) {
            $table->id('attendance_id');

            $table->unsignedBigInteger('pilgrim_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('activity_id')->nullable();

            $table->enum('status_type', [
                'ARRIVAL',
                'DEPARTURE',
                'ABSENT',
            ]);

            $table->dateTime('timestamp');

            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->text('supervisor_note')->nullable();

            // FK: pilgrim_id → pilgrims(pilgrim_id)
            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_tracking');
    }
};