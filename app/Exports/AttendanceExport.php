<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return Student::where('status', 'active')->with(['classrooms'])->get();
    }

    public function map($student): array
    {
        $attendances = Attendance::where('student_id', $student->id)
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->get();

        $hadir = $attendances->where('status', 'hadir')->count();
        $sakit = $attendances->where('status', 'sakit')->count();
        $izin = $attendances->where('status', 'izin')->count();
        $alpha = $attendances->where('status', 'alpha')->count();
        $terlambat = $attendances->where('late_minutes', '>', 0)->count();
        $totalTelatMenit = $attendances->sum('late_minutes');
        
        $classroom = $student->classrooms()->latest('joined_at')->first();
        $className = $classroom ? $classroom->name : '-';

        return [
            $student->nis,
            $student->name,
            $className,
            $hadir,
            $sakit,
            $izin,
            $alpha,
            $terlambat,
            $totalTelatMenit
        ];
    }

    public function headings(): array
    {
        return [
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Total Hadir',
            'Total Sakit',
            'Total Izin',
            'Total Alpha',
            'Kali Terlambat',
            'Total Terlambat (Menit)'
        ];
    }
}
