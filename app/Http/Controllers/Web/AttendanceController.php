<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Parameter Filter
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        // Ambil daftar kelas untuk dropdown filter
        $classrooms = Classroom::orderBy('name')->get();

        // Query Presensi
        $query = Attendance::with(['student.classrooms'])
            ->where('date', $date);

        if ($classroomId) {
            $query->whereHas('student.classrooms', function($q) use ($classroomId) {
                $q->where('classroom.id', $classroomId)
                  // Memastikan kelas yang diambil adalah kelas aktif siswa (joined_at <= hari ini dan belum mutasi)
                  ->where('joined_at', '<=', now())
                  ->whereNull('left_at');
            });
        }

        $attendances = $query->get();

        return view('attendance.index', compact('attendances', 'date', 'classroomId', 'classrooms'));
    }
}
