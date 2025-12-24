<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_attendees', function (Blueprint $table) {
            // Primary Key
            $table->id('attendee_id');

            // Foreign Keys
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('pilgrim_id');

            // بيانات المرافق
            $table->string('guest_name', 150);
            $table->string('passport_num', 50);
            $table->integer('guest_age');
            $table->decimal('ticket_price', 10, 2);

            // العلاقات
            $table->foreign('booking_id')
                ->references('booking_id')
                ->on('bookings')
                ->onDelete('cascade');

            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_attendees');
    }
};