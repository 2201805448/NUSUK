<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id('ticket_id');

            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('trip_id')->nullable();

            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('LOW');

            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'CLOSED'])->default('OPEN');

            // created_at فقط
            $table->dateTime('created_at')->useCurrent();

            // Foreign Key: user_id -> users(user_id) ON DELETE CASCADE
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};