<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Setting;
use App\Models\Attendance;
use App\Jobs\SendWaNotificationJob;
use Carbon\Carbon;

class CheckSpWarning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-sp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa dan kirim Surat Peringatan (SP) untuk siswa yang sering Alpha';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan SP otomatis...');

        $limitConsecutive = (int) Setting::getVal('sp_consecutive_alpha', 3);
        $limitTotalMonth = (int) Setting::getVal('sp_total_alpha', 5);

        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $today = Carbon::today()->format('Y-m-d');

        $students = Student::where('status', 'active')->with(['parent', 'classrooms.homeroomTeacher'])->get();

        foreach ($students as $student) {
            $attendances = Attendance::where('student_id', $student->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date', 'desc')
                ->get();

            // Cek 1: Total Alpha Bulan Ini
            $totalAlpha = $attendances->where('status', 'alpha')->count();
            
            // Cek 2: Alpha Berturut-turut (dari hari terakhir ke belakang)
            $consecutiveAlpha = 0;
            foreach ($attendances as $attn) {
                if ($attn->status === 'alpha') {
                    $consecutiveAlpha++;
                } else {
                    break;
                }
            }

            // Jika memenuhi syarat SP dan hari terakhir adalah HARI INI (untuk mencegah SP berulang di hari yang sama)
            // Asumsi: jika $consecutiveAlpha >= limit, pasti absen hari ini adalah alpha
            $todayLog = $attendances->where('date', $today)->first();
            
            if ($todayLog && $todayLog->status === 'alpha') {
                if ($consecutiveAlpha >= $limitConsecutive || $totalAlpha >= $limitTotalMonth) {
                    // Siapkan pesan SP
                    $message = "⚠️ *SURAT PERINGATAN DIGITAL*\n\n";
                    $message .= "Yth. Orang Tua/Wali dari ananda *{$student->name}*.\n";
                    $message .= "Kami informasikan bahwa ananda telah tercatat absen tanpa keterangan (Alpha) sebanyak {$totalAlpha} kali bulan ini (atau {$consecutiveAlpha} hari berturut-turut).\n\n";
                    $message .= "Mohon segera menghubungi pihak sekolah atau Wali Kelas untuk klarifikasi.";

                    // Kirim WA ke Orang Tua
                    if ($student->parent && $student->parent->phone) {
                        SendWaNotificationJob::dispatch($student->parent->phone, $message);
                    }

                    // Kirim WA ke Wali Kelas
                    $classroom = $student->classrooms->first();
                    if ($classroom && $classroom->homeroomTeacher && $classroom->homeroomTeacher->phone) {
                        $teacherMsg = "🚨 Laporan SP Otomatis: Siswa {$student->name} telah bolos {$totalAlpha}x bulan ini. Notifikasi telah dikirim ke orang tua.";
                        SendWaNotificationJob::dispatch($classroom->homeroomTeacher->phone, $teacherMsg);
                    }

                    $this->info("SP dikirim untuk siswa: {$student->name}");
                }
            }
        }

        $this->info('Pengecekan SP selesai.');
    }
}
