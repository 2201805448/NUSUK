<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pilgrim;
use Illuminate\Http\Request;

class PilgrimController extends Controller
{
    public function index()
    {
        $pilgrims = Pilgrim::with(['latestAttendance', 'user'])->get();

        $pilgrims->each(function ($pilgrim) {
            $pilgrim->status_type = $pilgrim->latestAttendance?->status_type;
            $pilgrim->supervisor_note = $pilgrim->latestAttendance?->supervisor_note;
        });

        return response()->json([
            'pilgrims' => $pilgrims
        ]);
    }
}
