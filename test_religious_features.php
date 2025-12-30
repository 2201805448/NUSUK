<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ReligiousContent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup User
$user = User::where('email', 'user_relig@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'User Religious',
        'email' => 'user_relig@example.com',
        'phone_number' => '1112229988',
        'password' => Hash::make('password'),
        'role' => 'USER',
    ]);
}
$token = $user->createToken('test-token')->plainTextToken;

// 2. Cleanup existing content for clean test
ReligiousContent::truncate();

// 3. Create Content (DUA and ATHKAR)
echo "Creating Content...\n";

// Helper to create content via API
function createContent($app, $token, $title, $category, $body)
{
    $req = Request::create('/api/guides', 'POST', [
        'title' => $title,
        'category' => $category,
        'body_text' => $body
    ]);
    $req->headers->set('Authorization', 'Bearer ' . $token);
    $req->headers->set('Accept', 'application/json');
    return $app->handle($req);
}

$res1 = createContent($app, $token, 'Morning Dua', 'DUA', 'O Allah, by your leave we live and die.');
echo "Create DUA: " . $res1->getStatusCode() . "\n";

$res2 = createContent($app, $token, 'Evening Athkar', 'ATHKAR', 'Subhan Allah 33 times.');
echo "Create ATHKAR: " . $res2->getStatusCode() . "\n";

$res3 = createContent($app, $token, 'Umrah Guide', 'GUIDE', 'Steps to perform Umrah...');
echo "Create GUIDE: " . $res3->getStatusCode() . "\n";

// 4. Test Filtering
echo "\nFetching DUAs...\n";
$reqFilter = Request::create('/api/guides?category=DUA', 'GET');
$reqFilter->headers->set('Authorization', 'Bearer ' . $token);
$reqFilter->headers->set('Accept', 'application/json');
$resFilter = $app->handle($reqFilter);
echo "Filter Status: " . $resFilter->getStatusCode() . "\n";
$body = $resFilter->getContent();
echo "Filter Body: " . substr($body, 0, 100) . "...\n";

if (strpos($body, 'Morning Dua') !== false && strpos($body, 'Evening Athkar') === false) {
    echo "SUCCESS: Filtering works (Found DUA, ignored ATHKAR).\n";
} else {
    echo "FAILURE: Filtering incorrect.\n";
}

// 5. Test Prayer Times
echo "\nFetching Prayer Times...\n";
$reqPrayer = Request::create('/api/prayer-times', 'GET');
$reqPrayer->headers->set('Authorization', 'Bearer ' . $token);
$reqPrayer->headers->set('Accept', 'application/json');
$resPrayer = $app->handle($reqPrayer);
echo "Prayer Times Status: " . $resPrayer->getStatusCode() . "\n";
$prayerBody = $resPrayer->getContent();
echo "Prayer Times: " . substr($prayerBody, 0, 100) . "...\n";

if (strpos($prayerBody, 'Makkah') !== false && strpos($prayerBody, 'Fajr') !== false) {
    echo "SUCCESS: Prayer times returned.\n";
} else {
    echo "FAILURE: Prayer times missing.\n";
}

// Cleanup
$user->delete();
