<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');

            $table->unsignedBigInteger('booking_id'); // INT NOT NULL
            $table->decimal('amount', 10, 2);

            $table->enum('pay_method', [
                'BANK_TRANSFER',
                'CARD',
                'CASH',
            ]);

            $table->dateTime('payment_date');

            $table->enum('payment_status', [
                'PENDING',
                'PAID',
                'FAILED',
            ])->default('PENDING');

            // FK: booking_id -> bookings(booking_id) ON DELETE CASCADE
            $table->foreign('booking_id')
                ->references('booking_id')
                ->on('bookings')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};