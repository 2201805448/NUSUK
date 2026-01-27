<?php
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Schema::getColumnListing('users');
print_r($columns);

if (in_array('fcm_token', $columns)) {
    echo "COLUMNEXISTS";
} else {
    echo "COLUMNMISSING";
}
