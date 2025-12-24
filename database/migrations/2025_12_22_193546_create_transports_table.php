<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transports', function (Blueprint $table) {
            $table->id('transport_id');

            // لازم تطابق trips.trip_id (BIGINT UNSIGNED)
            $table->unsignedBigInteger('trip_id');

            $table->string('transport_type', 50);
            $table->string('route_from', 100);
            $table->string('route_to', 100);
            $table->dateTime('departure_time');
            $table->text('notes')->nullable();

            $table->foreign('trip_id')
                  ->references('trip_id')
                  ->on('trips')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transports');
    }
};