<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pilgrims', function (Blueprint $table) {
            $table->id('pilgrim_id'); // INT AUTO_INCREMENT PRIMARY KEY

            $table->unsignedBigInteger('user_id');

            $table->string('passport_name', 150);
            $table->string('passport_number', 50);

            $table->string('passport_img', 255)->nullable();
            $table->string('visa_img', 255)->nullable();

            $table->string('nationality', 100);
            $table->date('date_of_birth')->nullable();

            $table->enum('gender', ['MALE', 'FEMALE'])->nullable();

            $table->string('emergency_call', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign Key
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilgrims');
    }
};