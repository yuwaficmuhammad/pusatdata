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

    public function create(Request $request)
    {
        $classrooms = Classroom::orderBy('name')->get();
        
        $classroomId = $request->input('classroom_id');
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        $students = collect();
        if ($classroomId) {
            $students = \App\Models\Student::whereHas('classrooms', function($q) use ($classroomId) {
                $q->where('classroom.id', $classroomId)
                  ->where('joined_at', '<=', now())
                  ->whereNull('left_at');
            })->orderBy('name')->get();
        }

        return view('attendance.create', compact('classrooms', 'classroomId', 'date', 'students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:student,id',
            'attendances.*.status' => 'required|in:hadir,terlambat,izin,sakit,alpha',
            'attendances.*.time_in' => 'nullable|date_format:H:i',
            'attendances.*.time_out' => 'nullable|date_format:H:i',
            'attendances.*.late_minutes' => 'nullable|integer|min:0'
        ]);

        foreach ($request->attendances as $attn) {
            // Fix missing seconds format if present (HTML time input usually sends H:i)
            $timeIn = $attn['time_in'] ?? null;
            if ($timeIn && strlen($timeIn) == 5) $timeIn .= ':00';
            
            $timeOut = $attn['time_out'] ?? null;
            if ($timeOut && strlen($timeOut) == 5) $timeOut .= ':00';

            Attendance::updateOrCreate(
                ['student_id' => $attn['student_id'], 'date' => $request->date],
                [
                    'status' => $attn['status'],
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'late_minutes' => $attn['late_minutes'] ?? 0,
                    'device_sn' => 'MANUAL_WEB_BATCH'
                ]
            );
        }

        return redirect()->route('attendance.index', ['date' => $request->date])
            ->with('success', 'Data presensi massal berhasil disimpan.');
    }

    public function edit($id)
    {
        $attendance = Attendance::with('student')->findOrFail($id);
        return view('attendance.edit', compact('attendance'));
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $request->validate([
            'status' => 'required|in:hadir,terlambat,izin,sakit,alpha',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'late_minutes' => 'nullable|integer|min:0'
        ]);

        // Fix missing seconds format
        $timeIn = $request->time_in;
        if ($timeIn && strlen($timeIn) == 5) $timeIn .= ':00';
        
        $timeOut = $request->time_out;
        if ($timeOut && strlen($timeOut) == 5) $timeOut .= ':00';

        $attendance->update([
            'status' => $request->status,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'late_minutes' => $request->late_minutes ?? 0,
            'device_sn' => 'MANUAL_WEB_UPDATE'
        ]);

        return redirect()->route('attendance.index', ['date' => $attendance->date->format('Y-m-d')])
            ->with('success', 'Data presensi berhasil diperbarui.');
    }

    public function export(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $fileName = 'Rekap_Absensi_' . $month . '_' . $year . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\AttendanceExport($month, $year), $fileName);
    }
}
