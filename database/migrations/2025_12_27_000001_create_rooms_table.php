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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id(); // PK

            $table->unsignedBigInteger('accommodation_id'); // FK to accommodation (Hotel)

            $table->string('room_number', 50);
            $table->integer('floor')->nullable();

            // Optional: override the hotel's room type if this specific room is different, 
            // or if the accommodation is just "Hilton" and this is "Suite 101"
            $table->string('room_type', 50)->nullable();

            $table->enum('status', ['AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'CLEANING'])
                ->default('AVAILABLE');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign Key
            $table->foreign('accommodation_id')
                ->references('accommodation_id')
                ->on('accommodations')
                ->onDelete('cascade');

            // Ensure unique room number per hotel
            $table->unique(['accommodation_id', 'room_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
