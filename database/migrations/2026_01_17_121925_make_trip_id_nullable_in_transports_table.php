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
        Schema::table('transports', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['trip_id']);

            // Make trip_id nullable
            $table->unsignedBigInteger('trip_id')->nullable()->change();

            // Re-add the foreign key constraint with nullable support
            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['trip_id']);

            // Make trip_id non-nullable again
            $table->unsignedBigInteger('trip_id')->nullable(false)->change();

            // Re-add the foreign key constraint
            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->onDelete('cascade');
        });
    }
};
