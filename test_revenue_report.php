<?php
/**
 * Test Script: Revenue Reporting
 * Tests date filtering and revenue calculation.
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Package;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

try {
    echo "=== Testing Revenue Report ===\n\n";

    // 1. Setup Data
    echo "--- Setting up test data ---\n";

    // Admin
    $admin = User::firstOrCreate(['email' => 'admin_report_test@nusuk.com'], [
        'email' => 'admin_report_test@nusuk.com',
        'password' => Hash::make('password'),
        'full_name' => 'Report Test Admin',
        'role' => 'ADMIN',
        'phone_number' => '0500000000'
    ]);

    // Trip & Package
    $package = Package::firstOrCreate(['package_name' => 'Report Package'], ['price' => 1000, 'duration_days' => 5]);
    $trip = Trip::firstOrCreate(['trip_name' => 'Report Trip'], [
        'package_id' => $package->package_id,
        'start_date' => now()->addDays(30),
        'end_date' => now()->addDays(35),
        'status' => 'PLANNED'
    ]);

    // Helper to create booking and payment
    function createTransaction($user, $trip, $date, $amount, $status)
    {
        // Generate unique email for dummy user to avoid unique constraint if we created new users every time
        // But we are passing $user. 
        // The error might be in Booking creation if booking_ref is not unique enough or something else.
        // Booking ref uses uniqid(), should be fine.
        // Let's wrap inner creation too or just see global error.

        $booking = Booking::create([
            'user_id' => $user->user_id,
            'package_id' => $trip->package_id,
            'trip_id' => $trip->trip_id,
            'booking_ref' => 'REP-' . uniqid() . '-' . rand(100, 999),
            'booking_date' => $date,
            'total_price' => $amount,
            'status' => 'CONFIRMED'
        ]);

        Payment::create([
            'booking_id' => $booking->booking_id,
            'amount' => $amount,
            'payment_date' => $date,
            'payment_status' => $status, // PAID, PENDING, FAILED
            'pay_method' => 'CARD'
        ]);
    }

    // User
// Use firstOrCreate for the dummy user
    $user = User::firstOrCreate(['email' => 'user_report_test@nusuk.com'], [
        'email' => 'user_report_test@nusuk.com',
        'password' => Hash::make('password'),
        'full_name' => 'Report Test User',
        'role' => 'PILGRIM',
        'phone_number' => '0599999999'
    ]);

    // 2. Create Transactions relative to TODAY
    $today = Carbon::now()->format('Y-m-d');
    $yesterday = Carbon::now()->subDay()->format('Y-m-d');
    $lastMonth = Carbon::now()->subMonth()->format('Y-m-d');

    // Record 1: Today, PAID, 1000
    createTransaction($user, $trip, $today, 1000, 'PAID');

    // Record 2: Yesterday, PAID, 500
    createTransaction($user, $trip, $yesterday, 500, 'PAID');

    // Record 3: Today, PENDING, 2000 (Should count in transactions but not revenue)
    createTransaction($user, $trip, $today, 2000, 'PENDING');

    // Record 4: Last Month, PAID, 5000 (Outside range)
    createTransaction($user, $trip, $lastMonth, 5000, 'PAID');

    // 3. Call API for "Last 7 Days" Range
    echo "\n--- Requesting Report ($yesterday to $today) ---\n";

    $startDate = $yesterday;
    $endDate = $today;

    // Calculate Expectation Dynamically
    $expectedRevenue = Payment::whereDate('payment_date', '>=', $startDate)
        ->whereDate('payment_date', '<=', $endDate)
        ->where('payment_status', 'PAID')
        ->sum('amount');

    $expectedTransactions = Payment::whereDate('payment_date', '>=', $startDate)
        ->whereDate('payment_date', '<=', $endDate)
        ->count();

    $response = app()->call('App\Http\Controllers\Api\ReportController@revenueReport', [
        'request' => \Illuminate\Http\Request::create('/reports/revenue', 'GET', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ])
    ]);

    $data = $response->getData();

    echo "Meta: Start={$data->meta->start_date}, End={$data->meta->end_date}\n";
    echo "Summary:\n";
    echo "  Total Revenue: " . $data->summary->total_revenue . " (Expected: $expectedRevenue)\n";
    echo "  Total Transactions: " . $data->summary->total_transactions . " (Expected: $expectedTransactions)\n";
    echo "  Successful: " . $data->summary->successful_transactions . " (Expected: 2)\n";
    echo "  Pending: " . $data->summary->pending_transactions . " (Expected: 1)\n";

    if ($data->summary->total_revenue == $expectedRevenue && $data->summary->total_transactions == $expectedTransactions) {
        echo "✓ SUCCESS: Revenue and Transaction counts match expected values.\n";
    } else {
        echo "✗ FAILED: Counts do not match.\n";
    }

    // 4. Verify Record Details
    echo "\n--- Verifying Records List ---\n";
    $foundOutside = false;
    foreach ($data->records as $record) {
        if ($record->payment_date === $lastMonth) {
            $foundOutside = true;
        }
        echo "- Date: {$record->payment_date}, Amount: {$record->amount}, Status: {$record->status}\n";
    }

    if (!$foundOutside) {
        echo "✓ SUCCESS: Did not find records outside date range.\n";
    } else {
        echo "✗ FAILED: Found records from outside date range.\n";
    }

    echo "\n=== Test Complete ===\n";

} catch (\Throwable $e) {
    file_put_contents('error_log.txt', "Global Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "CRITICAL ERROR CAUGHT. CHECK error_log.txt\n";
}
