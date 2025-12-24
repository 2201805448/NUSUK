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
        Schema::create('religious_content', function (Blueprint $table) {
            $table->id('content_id');

            $table->string('title', 200);

            $table->enum('category', [
                'PRAYER',
                'GUIDE',
                'HADITH',
                'GENERAL'
            ])->default('GENERAL');

            $table->text('body_text');

            $table->string('image_url', 255)->nullable();

            // created_at فقط
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('religious_content');
    }
};