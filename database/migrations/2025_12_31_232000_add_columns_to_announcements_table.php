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
        Schema::table('announcements', function (Blueprint $table) {
            $table->enum('type', ['GENERAL', 'PACKAGE', 'TRIP', 'OFFER'])->default('GENERAL')->after('content');
            $table->unsignedBigInteger('related_id')->nullable()->after('type');
            $table->enum('priority', ['NORMAL', 'HIGH', 'URGENT'])->default('NORMAL')->after('is_active');
            $table->dateTime('start_date')->nullable()->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['type', 'related_id', 'priority', 'start_date']);
        });
    }
};
