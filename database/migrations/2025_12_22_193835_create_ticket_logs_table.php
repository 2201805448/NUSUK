<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_logs', function (Blueprint $table) {
            // Primary Key
            $table->id('log_id');

            // Foreign Keys
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('action_by');

            // بيانات السجل
            $table->text('action_note');

            // created_at فقط
            $table->dateTime('created_at')->useCurrent();

            // العلاقات
            $table->foreign('ticket_id')
                ->references('ticket_id')
                ->on('tickets')
                ->onDelete('cascade');

            $table->foreign('action_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_logs');
    }
};