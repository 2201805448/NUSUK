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
        Schema::create('pilgrim_notes', function (Blueprint $table) {
            $table->id('note_id');

            $table->unsignedBigInteger('pilgrim_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('group_id')->nullable();

            $table->enum('note_type', [
                'FEEDBACK',      // General feedback
                'SUGGESTION',    // Suggestions for improvement
                'COMPLAINT',     // Complaints
                'REQUEST',       // Special requests
                'OBSERVATION',   // Observations/remarks
                'OTHER'          // Other
            ])->default('FEEDBACK');

            $table->text('note_text');
            $table->enum('category', [
                'ACCOMMODATION', // Related to hotels/rooms
                'TRANSPORT',     // Related to transportation
                'FOOD',          // Related to meals
                'SCHEDULE',      // Related to trip schedule/activities
                'SERVICE',       // Related to services
                'STAFF',         // Related to staff/supervisor
                'GENERAL'        // General notes
            ])->default('GENERAL');

            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM');
            $table->enum('status', ['PENDING', 'REVIEWED', 'RESOLVED', 'DISMISSED'])->default('PENDING');

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('response')->nullable();
            $table->dateTime('reviewed_at')->nullable();

            $table->dateTime('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('pilgrim_id')
                ->references('pilgrim_id')
                ->on('pilgrims')
                ->onDelete('cascade');

            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->onDelete('cascade');

            $table->foreign('group_id')
                ->references('group_id')
                ->on('groups_trips')
                ->onDelete('set null');

            $table->foreign('reviewed_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilgrim_notes');
    }
};
