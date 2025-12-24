<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->bigIncrements('activity_id');

            // ✅ لازم تطابق trips.trip_id
            $table->unsignedBigInteger('trip_id');

            $table->string('activity_type', 100);
            $table->string('location', 150);

            $table->date('activity_date');
            $table->time('activity_time');

            $table->enum('status', ['SCHEDULED', 'DONE', 'CANCELLED'])->default('SCHEDULED');

            $table->foreign('trip_id')
                  ->references('trip_id')
                  ->on('trips')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};