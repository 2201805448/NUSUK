<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrayerTimeController extends Controller
{
    /**
     * Get prayer times for Makkah and Madinah.
     */
    public function index()
    {
        // In a real production app, this would query an external API (like Aladhan API) 
        // or calculate based on coordinates and date.
        // For this task, we will return a structured JSON response with mock data for "Today".

        $date = date('Y-m-d');

        $times = [
            'date' => $date,
            'Makkah' => [
                'Fajr' => '05:39',
                'Sunrise' => '06:58',
                'Dhuhr' => '12:21',
                'Asr' => '15:23',
                'Maghrib' => '17:44',
                'Isha' => '19:14'
            ],
            'Madinah' => [
                'Fajr' => '05:43',
                'Sunrise' => '07:05',
                'Dhuhr' => '12:22',
                'Asr' => '15:19',
                'Maghrib' => '17:39',
                'Isha' => '19:09'
            ]
        ];

        return response()->json($times);
    }
}
