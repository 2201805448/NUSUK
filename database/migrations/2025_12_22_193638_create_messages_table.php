<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');

            // لازم تطابق users.user_id (BIGINT UNSIGNED)
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');

            $table->text('content');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('sender_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('receiver_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};