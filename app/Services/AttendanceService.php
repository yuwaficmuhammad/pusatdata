<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(private ScheduleService $scheduleService)
    {
    }

    /**
     * Parse ADMS raw text and record attendance.
     */
    public function processAdmsPush(string $deviceSn, string $rawBody): int
    {
        $lines = explode("\n", trim($rawBody));
        $savedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // ADMS Format: [USER_ID] [DATE] [TIME] [STATUS] [VERIFY_MODE]
            // Example: 1001 2026-08-23 07:05:12 0 1
            $parts = preg_split('/\s+/', $line);
            
            if (count($parts) >= 4) {
                $userId = $parts[0];
                $date = $parts[1];
                $time = $parts[2];
                // Status: 0=Masuk, 1=Keluar (tergantung setingan mesin, biasanya kita ambil waktu absensi pertama sebagai IN, terakhir sebagai OUT)
                // Kita gunakan logika de-duplikasi harian
                
                try {
                    $this->recordAttendance($deviceSn, $userId, $date, $time);
                    $savedCount++;
                } catch (\Exception $e) {
                    dump($e->getMessage());
                    Log::error("Gagal memproses absensi ADMS baris: {$line}. Error: " . $e->getMessage());
                }
            }
        }

        return $savedCount;
    }

    /**
     * Record single attendance entry.
     */
    private function recordAttendance(string $deviceSn, string $studentNis, string $date, string $time)
    {
        // Cari siswa berdasarkan NIS (karena USER_ID di ADMS biasanya = NIS siswa)
        $student = Student::where('nis', $studentNis)->first();
        if (!$student) {
            return; // Skip jika siswa tidak ditemukan
        }

        DB::transaction(function () use ($student, $deviceSn, $date, $time) {
            // Cari kelas siswa yang aktif saat ini
            $studentClassroom = $student->classrooms()->latest('joined_at')->first();
            $classroomId = $studentClassroom ? $studentClassroom->id : 0;

            // Dapatkan informasi jadwal hari ini
            $scheduleInfo = $this->scheduleService->getTodaySchedule($classroomId, $date);
            
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('date', $date)
                ->first();

            if (!$attendance) {
                // TAP PERTAMA (MASUK)
                
                // Kalkulasi Keterlambatan
                $status = 'hadir';
                $lateMinutes = 0;
                $scheduleVersionId = null;

                if (!$scheduleInfo['is_holiday'] && count($scheduleInfo['schedules']) > 0) {
                    $firstSchedule = $scheduleInfo['schedules']->first();
                    $scheduleVersionId = $firstSchedule->schedule_version_id;
                    
                    $startTime = Carbon::parse($date . ' ' . $firstSchedule->timeSlot->start_time);
                    $actualTime = Carbon::parse($date . ' ' . $time);
                    
                    if ($actualTime->gt($startTime)) {
                        $status = 'terlambat';
                        $lateMinutes = $startTime->diffInMinutes($actualTime);
                    }
                } elseif ($scheduleInfo['is_holiday']) {
                    // Jika absen di hari libur, kita bisa catat saja tapi mungkin tidak masuk perhitungan.
                    $status = 'hadir';
                }

                $attendance = Attendance::create([
                    'student_id' => $student->id,
                    'schedule_version_id' => $scheduleVersionId,
                    'date' => $date,
                    'time_in' => $time,
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'device_sn' => $deviceSn,
                ]);

                // Log Activity
                ActivityLog::create([
                    'user_id' => $student->user_id,
                    'action' => 'attendance_in',
                    'description' => "Siswa tap masuk pada {$time}",
                    'ip_address' => request()->ip(),
                ]);

            } else {
                // TAP KEDUA / TERAKHIR (PULANG)
                // Kita selalu update time_out dengan tap terakhir
                $attendance->update([
                    'time_out' => $time,
                    'device_sn' => $deviceSn,
                ]);

                // Log Activity
                ActivityLog::create([
                    'user_id' => $student->user_id,
                    'action' => 'attendance_out',
                    'description' => "Siswa tap pulang pada {$time}",
                    'ip_address' => request()->ip(),
                ]);
            }
            
            // Dispatch Fonnte Notification Job
            if (!empty($student->parent_phone)) {
                $statusText = $attendance->status === 'hadir' ? 'Tepat Waktu' : strtoupper($attendance->status);
                $jenisAbsen = $attendance->time_out ? 'Pulang' : 'Datang';
                $jamAbsen = $attendance->time_out ? $attendance->time_out : $attendance->time_in;
                
                $message = "Halo Bapak/Ibu Wali dari {$student->name},\n\n";
                $message .= "Ini adalah notifikasi absensi sekolah:\n";
                $message .= "- Jenis: {$jenisAbsen}\n";
                $message .= "- Waktu: {$jamAbsen}\n";
                $message .= "- Status: {$statusText}\n";
                
                if ($attendance->status === 'terlambat' && !$attendance->time_out) {
                    $message .= "- Keterlambatan: {$attendance->late_minutes} Menit\n";
                }
                
                $message .= "\nTerima kasih,\nSistem Pusat Data SMK Salafiyah";
                
                \App\Jobs\SendWaNotificationJob::dispatch($student->parent_phone, $message);
            }
        });
    }
}
