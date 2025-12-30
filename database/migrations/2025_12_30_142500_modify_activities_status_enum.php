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
        // Using raw SQL because Doctrine DBAL (used by Laravel 'change') sometimes struggles with ENUMs
        // This syntax is for MySQL/MariaDB which is commonly used.
        // If SQLite (for testing), it doesn't support ALTER COLUMN to modify type easily, but usually testing DBs are rebuilt.

        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE activities MODIFY COLUMN status ENUM('SCHEDULED', 'IN_PROGRESS', 'DONE', 'CANCELLED') NOT NULL DEFAULT 'SCHEDULED'");
        } else {
            // Fallback for others (like SQLite), usually safe to just ignore strict enum check in sqlite or handle differently
            // For SQLite, enums are typically just text constraints. 
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Reverting back to original enum values. Warning: Data with 'IN_PROGRESS' might be truncated or cause error.
            // We'll map IN_PROGRESS to SCHEDULED before reverting to avoid error if possible, or just force it.
            DB::statement("UPDATE activities SET status = 'SCHEDULED' WHERE status = 'IN_PROGRESS'");
            DB::statement("ALTER TABLE activities MODIFY COLUMN status ENUM('SCHEDULED', 'DONE', 'CANCELLED') NOT NULL DEFAULT 'SCHEDULED'");
        }
    }
};
