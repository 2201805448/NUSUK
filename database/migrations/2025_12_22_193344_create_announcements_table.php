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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id('announcement_id');

            $table->string('title', 150);

            $table->text('content');

            $table->string('image_url', 255)->nullable();

            $table->boolean('is_active')->default(1);

            // created_at فقط (بدون updated_at)
            $table->timestamp('created_at')->useCurrent();

            $table->date('expiry_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};