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
        Schema::create('trip_updates', function (Blueprint $table) {
            $table->id('update_id');

            $table->unsignedBigInteger('trip_id');

            $table->string('title', 150);
            $table->text('message');

            $table->unsignedBigInteger('created_by'); // User ID (Supervisor)

            $table->dateTime('created_at')->useCurrent();

            // Foreign Keys
            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_updates');
    }
};
