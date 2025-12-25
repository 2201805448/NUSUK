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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id'); // INT AUTO_INCREMENT PRIMARY KEY

            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->string('phone_number', 30);
            $table->string('password', 255);

            $table->enum('role', [
                'PILGRIM',
                'SUPERVISOR',
                'ADMIN',
                'SUPPORT',
                'USER'
            ]);

            $table->enum('account_status', [
                'ACTIVE',
                'INACTIVE',
                'BLOCKED'
            ])->default('ACTIVE');

            // created_at & updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
