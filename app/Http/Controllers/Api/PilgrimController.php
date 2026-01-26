<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pilgrim;
use Illuminate\Http\Request;

class PilgrimController extends Controller
{
    public function index()
    {
        $pilgrims = Pilgrim::with(['latestAttendance'])->get();

        $pilgrims->each(function ($pilgrim) {
            $pilgrim->status_type = $pilgrim->latestAttendance ? $pilgrim->latestAttendance->status_type : null;
        });

        return response()->json([
            'pilgrims' => $pilgrims
        ]);
    }
}
