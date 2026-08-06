<?php

namespace App\Http\Controllers;

use App\Models\BasicListeningSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BasicListeningScheduleController extends Controller
{
    public function index()
    {
        $now    = Carbon::now('Asia/Jakarta');
        $map    = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
        $today  = $map[$now->dayOfWeekIso];

        $currentSchedules = BasicListeningSchedule::activeNow()->get();
        $allToday = BasicListeningSchedule::where('hari', $today)
            ->orderBy('jam_mulai')
            ->get();

        return view('bl.schedule-monitor', compact('now', 'currentSchedules', 'allToday'));
    }
}
