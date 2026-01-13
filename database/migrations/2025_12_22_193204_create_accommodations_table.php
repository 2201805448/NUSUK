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
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id('accommodation_id'); // INT AUTO_INCREMENT PRIMARY KEY

            $table->string('hotel_name', 150);
            $table->string('city', 100);
            $table->string('room_type', 50);
            $table->integer('capacity');
            $table->text('notes')->nullable();
            $table->integer('start')->nullable(); // Star rating
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
