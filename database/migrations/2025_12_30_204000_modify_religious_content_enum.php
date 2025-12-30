<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify column using raw SQL to be safe with Enums
        // We are adding 'DUA' and 'ATHKAR' to the list
        DB::statement("ALTER TABLE religious_content MODIFY COLUMN category ENUM('PRAYER','GUIDE','HADITH','GENERAL','DUA','ATHKAR') DEFAULT 'GENERAL'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original
        DB::statement("ALTER TABLE religious_content MODIFY COLUMN category ENUM('PRAYER','GUIDE','HADITH','GENERAL') DEFAULT 'GENERAL'");
    }
};
