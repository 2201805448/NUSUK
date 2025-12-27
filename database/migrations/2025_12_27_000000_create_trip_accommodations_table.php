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
        Schema::create('trip_accommodations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('accommodation_id');

            // Optional: You might want to know check-in/out dates for this specific trip-hotel association if it's trip-specific, 
            // but the prompt is just "enter approved hotel data within trips".

            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->onDelete('cascade');

            $table->foreign('accommodation_id')
                ->references('accommodation_id')
                ->on('accommodations')
                ->onDelete('cascade'); // If accommodation is deleted, remove link.

            $table->timestamps();

            // Prevent duplicate linking of the same hotel to the same trip
            $table->unique(['trip_id', 'accommodation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_accommodations');
    }
};
