<?php
use App\Models\Pilgrim;
use App\Models\AttendanceTracking;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Debug: Controller test 2\n";

try {
    echo "Check AttendanceModel...\n";
    $count = AttendanceTracking::count();
    echo "Attendance count: $count\n";

    echo "Running relation query...\n";
    $pilgrims = Pilgrim::with(['latestAttendance'])->get();
    echo "Query success. Count: " . $pilgrims->count() . "\n";

} catch (\Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
