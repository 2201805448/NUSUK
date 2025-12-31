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
        Schema::create('group_accommodations', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('accommodation_id');

            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            // Composite primary key
            $table->primary(['group_id', 'accommodation_id']);

            // Foreign keys
            $table->foreign('group_id')
                ->references('group_id')
                ->on('groups_trips')
                ->onDelete('cascade');

            $table->foreign('accommodation_id')
                ->references('accommodation_id')
                ->on('accommodations')
                ->onDelete('cascade');

            $table->foreign('assigned_by')
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
        Schema::dropIfExists('group_accommodations');
    }
};
