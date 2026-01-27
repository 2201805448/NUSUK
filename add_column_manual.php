<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Attempting to add fcm_token column...\n";

    // Check if exists first
    if (Schema::hasColumn('users', 'fcm_token')) {
        echo "Column already exists.\n";
        exit(0);
    }

    // Add column
    Schema::table('users', function (Blueprint $table) {
        $table->string('fcm_token')->nullable()->after('remember_token');
    });

    echo "Column added successfully via Schema Builder.\n";

} catch (\Exception $e) {
    echo "Schema Builder Schema Failed: " . $e->getMessage() . "\n";
    echo "Trying raw SQL...\n";
    try {
        DB::statement("ALTER TABLE `users` ADD `fcm_token` VARCHAR(255) NULL AFTER `remember_token`");
        echo "Column added successfully via Raw SQL.\n";
    } catch (\Exception $ex) {
        echo "Raw SQL Failed: " . $ex->getMessage() . "\n";
        exit(1);
    }
}
