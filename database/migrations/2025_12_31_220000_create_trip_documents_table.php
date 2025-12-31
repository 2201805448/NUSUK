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
        Schema::create('trip_documents', function (Blueprint $table) {
            $table->id('document_id');

            $table->unsignedBigInteger('trip_id');

            $table->string('title', 200);
            $table->string('description', 500)->nullable();
            $table->enum('document_type', ['PROGRAM', 'INSTRUCTIONS', 'VISA', 'TICKET', 'MAP', 'GUIDE', 'OTHER'])->default('OTHER');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50)->nullable(); // pdf, docx, jpg, etc.
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes

            $table->boolean('is_public')->default(true); // visible to all trip pilgrims
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('trip_id')
                ->references('trip_id')
                ->on('trips')
                ->onDelete('cascade');

            $table->foreign('uploaded_by')
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
        Schema::dropIfExists('trip_documents');
    }
};
