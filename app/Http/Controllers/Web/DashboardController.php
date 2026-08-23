<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $user = $request->user();
        $classroomId = null;

        // Jika user adalah wali kelas, batasi data hanya untuk kelasnya
        if ($user && $user->role === 'homeroom_teacher' && $user->teacher) {
            $classroom = \App\Models\Classroom::where('homeroom_teacher_id', $user->teacher->id)->first();
            if ($classroom) {
                $classroomId = $classroom->id;
            }
        }

        // Query builder dasar untuk siswa aktif
        $studentQuery = Student::where('status', 'active');
        if ($classroomId) {
            $studentQuery->whereHas('classrooms', function($q) use ($classroomId) {
                $q->where('classroom.id', $classroomId)
                  ->where('joined_at', '<=', now())
                  ->whereNull('left_at');
            });
        }
        $totalStudents = $studentQuery->count();

        // 1. Data Pie Chart Hari Ini
        $attendancesTodayQuery = Attendance::where('date', $today);
        if ($classroomId) {
            $attendancesTodayQuery->whereHas('student.classrooms', function($q) use ($classroomId) {
                $q->where('classroom.id', $classroomId)
                  ->where('joined_at', '<=', now())
                  ->whereNull('left_at');
            });
        }
        $attendancesToday = $attendancesTodayQuery->get();

        $statsToday = [
            'hadir' => $attendancesToday->whereIn('status', ['hadir', 'terlambat'])->count(),
            'sakit' => $attendancesToday->where('status', 'sakit')->count(),
            'izin' => $attendancesToday->where('status', 'izin')->count(),
            'alpha' => $attendancesToday->where('status', 'alpha')->count(),
        ];
        
        // Asumsi sisa siswa yang belum ada log = belum absen
        $statsToday['belum_absen'] = max(0, $totalStudents - array_sum($statsToday));

        // 2. Data Bar Chart (Menit Keterlambatan 7 Hari Terakhir)
        $pastWeek = Carbon::today()->subDays(6)->format('Y-m-d');
        
        $lateQuery = Attendance::whereBetween('date', [$pastWeek, $today])
            ->select('date', DB::raw('SUM(late_minutes) as total_late'))
            ->groupBy('date')
            ->orderBy('date');
            
        if ($classroomId) {
            $lateQuery->whereHas('student.classrooms', function($q) use ($classroomId) {
                $q->where('classroom.id', $classroomId)
                  ->where('joined_at', '<=', now())
                  ->whereNull('left_at');
            });
        }
        
        $lateTrendRaw = $lateQuery->get()->keyBy('date');
        
        $lateTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateLabel = Carbon::today()->subDays($i)->format('Y-m-d');
            $lateTrend['labels'][] = Carbon::today()->subDays($i)->format('D, d M');
            $lateTrend['data'][] = isset($lateTrendRaw[$dateLabel]) ? (int)$lateTrendRaw[$dateLabel]->total_late : 0;
        }

        return view('dashboard.index', compact('statsToday', 'lateTrend', 'totalStudents'));
    }
}
