<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');

            // ✅ لازم تطابق $table->id() في الجداول الأخرى (BIGINT UNSIGNED)
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('trip_id');

            $table->string('booking_ref', 50)->unique(); // خليها nullable() لو تبي
            $table->dateTime('booking_date')->useCurrent();

            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->string('pay_method', 50)->nullable();

            $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED', 'REJECTED'])->default('PENDING');
            $table->text('request_notes')->nullable();
            $table->text('admin_reply')->nullable();

            // Foreign keys
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('package_id')->references('package_id')->on('packages')->onDelete('restrict');
            $table->foreign('trip_id')->references('trip_id')->on('trips')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};