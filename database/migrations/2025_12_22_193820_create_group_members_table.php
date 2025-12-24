<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table) {
            // الأعمدة الأساسية
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('pilgrim_id');

            // بيانات إضافية
            $table->dateTime('join_date')->useCurrent();
            $table->enum('member_status', [
                'ACTIVE',
                'REMOVED',
            ])->default('ACTIVE');

            // Primary Key مركب
            $table->primary(['group_id', 'pilgrim_id']);

            // Foreign Keys
            $table->foreign('group_id')
                ->references('group_id')
                ->on('groups_trips')
                ->onDelete('cascade');

            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};