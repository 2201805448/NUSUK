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
        // 1. Create drivers table
        Schema::create('drivers', function (Blueprint $table) {
            $table->id('driver_id'); // Primary key

            $table->string('name', 150);
            $table->string('license_number', 50)->unique();
            $table->string('phone_number', 30);

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();
        });

        // 2. Add driver_id to transports table
        Schema::table('transports', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->nullable()->after('trip_id');

            $table->foreign('driver_id')
                ->references('driver_id')
                ->on('drivers')
                ->onDelete('set null'); // Keep transport record even if driver is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::dropIfExists('drivers');
    }
};
