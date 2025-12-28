<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_modifications', function (Blueprint $table) {
            $table->id('modification_id');
            $table->unsignedBigInteger('booking_id');
            $table->string('request_type'); // e.g. CHANGE_COMPANIONS, CHANGE_DATE
            $table->json('request_data');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_modifications');
    }
};
