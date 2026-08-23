<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Services\ScheduleService;
use Carbon\Carbon;

class MarkAlphaAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-alpha';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menandai siswa yang tidak hadir sebagai Alpha';

    /**
     * Execute the console command.
     */
    public function handle(ScheduleService $scheduleService)
    {
        $date = Carbon::today()->format('Y-m-d');
        $this->info("Memulai pengecekan Alpha untuk tanggal: {$date}");

        // Ambil semua siswa aktif
        $students = Student::where('status', 'active')->with('user')->get();
        $alphaCount = 0;

        foreach ($students as $student) {
            // Cari kelas siswa yang aktif saat ini
            $studentClassroom = $student->classrooms()->latest('joined_at')->first();
            $classroomId = $studentClassroom ? $studentClassroom->id : 0;

            // Dapatkan informasi jadwal hari ini
            $scheduleInfo = $scheduleService->getTodaySchedule($classroomId, $date);

            // Jika hari libur atau tidak ada jadwal, lewati
            if ($scheduleInfo['is_holiday'] || $scheduleInfo['schedules']->isEmpty()) {
                continue;
            }

            // Cek apakah siswa sudah punya data absensi hari ini (Hadir, Izin, Sakit, Terlambat)
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('date', $date)
                ->first();

            // Jika belum ada absen sama sekali, tandai Alpha
            if (!$attendance) {
                // Ambil ID versi jadwal dari jadwal pertama
                $firstSchedule = $scheduleInfo['schedules']->first();
                $scheduleVersionId = $firstSchedule->schedule_version_id ?? null;

                Attendance::create([
                    'student_id' => $student->id,
                    'schedule_version_id' => $scheduleVersionId,
                    'date' => $date,
                    'time_in' => null,
                    'time_out' => null,
                    'status' => 'alpha',
                    'late_minutes' => 0,
                    'device_sn' => 'SYSTEM',
                ]);
                $alphaCount++;

                // Kirim notifikasi WA ke orang tua
                if (!empty($student->parent_phone)) {
                    $message = "Halo Bapak/Ibu Wali dari {$student->name},\n\n";
                    $message .= "Kami informasikan bahwa ananda hari ini (Status: ALPHA) / Tidak Hadir tanpa keterangan.\n\n";
                    $message .= "Harap hubungi pihak sekolah/wali kelas jika ada keperluan yang membuat ananda berhalangan hadir.\n\n";
                    $message .= "Terima kasih,\nSistem Pusat Data SMK Salafiyah";
                    
                    \App\Jobs\SendWaNotificationJob::dispatch($student->parent_phone, $message);
                }

                // Push Notif ke Anak
                if ($student->user && !empty($student->user->fcm_token)) {
                    \App\Jobs\SendFcmNotificationJob::dispatch(
                        $student->user->fcm_token, 
                        "Peringatan Ketidakhadiran", 
                        "Anda tercatat Alpha (Tidak Hadir) hari ini. Harap segera melapor jika ini sebuah kesalahan."
                    );
                }
            }
        }

        $this->info("Selesai. Total siswa ditandai Alpha: {$alphaCount}");
    }
}
