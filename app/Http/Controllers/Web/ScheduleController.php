<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ScheduleVersion;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        // Ambil versi jadwal yang sedang aktif
        $activeVersion = ScheduleVersion::with([
            'semester.academicYear',
            'activeDays.schedules.subject',
            'activeDays.schedules.timeSlot',
            'activeDays.schedules.teacher',
            'activeDays.schedules.classroom'
        ])->where('is_active', true)->first();

        // Ambil semua versi jadwal untuk dropdown pilihan (opsional)
        $versions = ScheduleVersion::with('semester.academicYear')->get();

        return view('schedules.index', compact('activeVersion', 'versions'));
    }
}
