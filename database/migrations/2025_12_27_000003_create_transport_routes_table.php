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
        // 1. Create transport_routes table
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id(); // PK id

            $table->string('route_name', 150);
            $table->string('start_location', 100);
            $table->string('end_location', 100);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('estimated_duration_mins')->nullable();

            $table->timestamps();
        });

        // 2. Add route_id to transports table
        Schema::table('transports', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')->nullable()->after('driver_id');

            $table->foreign('route_id')
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
            $table->dropForeign(['route_id']);
            $table->dropColumn('route_id');
        });

        Schema::dropIfExists('transport_routes');
    }
};
