<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups_trips', function (Blueprint $table) {
            $table->bigIncrements('group_id');

            // لازم تطابق trips.trip_id و users.user_id
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('supervisor_id');

            $table->string('group_code', 50);

            $table->enum('group_status', ['ACTIVE', 'FINISHED'])->default('ACTIVE');

            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->onDelete('cascade');

            $table->foreign('supervisor_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups_trips');
    }
};