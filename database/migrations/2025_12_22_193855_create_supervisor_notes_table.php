<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_notes', function (Blueprint $table) {
            // Primary Key
            $table->id('note_id');

            // Foreign Keys
            $table->unsignedBigInteger('pilgrim_id');
            $table->unsignedBigInteger('supervisor_id');
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();

            // بيانات الملاحظة
            $table->string('note_type', 50)->nullable();
            $table->text('note_text');

            // created_at فقط
            $table->dateTime('created_at')->useCurrent();

            // العلاقات
            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('cascade');

            $table->foreign('supervisor_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_notes');
    }
};