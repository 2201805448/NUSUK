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
        Schema::create('trips', function (Blueprint $table) {
            $table->id('trip_id');

            $table->string('trip_name', 150);

            $table->date('start_date');

            $table->date('end_date');

            $table->enum('status', [
                'PLANNED',
                'ONGOING',
                'COMPLETED',
                'CANCELLED'
            ])->default('PLANNED');

            $table->integer('capacity')->nullable();

            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};