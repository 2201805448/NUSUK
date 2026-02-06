<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Changes route_from and route_to from string to unsignedBigInteger
     * to reference transport_routes table IDs
     */
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            // Drop old string columns
            $table->dropColumn(['route_from', 'route_to']);
        });

        Schema::table('transports', function (Blueprint $table) {
            // Add new integer columns that reference transport_routes
            $table->unsignedBigInteger('route_from')->nullable()->after('route_id');
            $table->unsignedBigInteger('route_to')->nullable()->after('route_from');

            // Add foreign keys
            $table->foreign('route_from')
                ->references('id')
                ->on('transport_routes')
                ->onDelete('set null');

            $table->foreign('route_to')
                ->references('id')
                ->on('transport_routes')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['route_from']);
            $table->dropForeign(['route_to']);
            $table->dropColumn(['route_from', 'route_to']);
        });

        Schema::table('transports', function (Blueprint $table) {
            // Restore original string columns
            $table->string('route_from', 100)->nullable()->after('route_id');
            $table->string('route_to', 100)->nullable()->after('route_from');
        });
    }
};
