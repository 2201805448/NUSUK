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
        Schema::create('trip_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('user_id');

            $table->text('content');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('trip_id')->references('trip_id')->on('trips')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_messages');
    }
};
