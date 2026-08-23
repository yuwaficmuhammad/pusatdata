<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\UnknownAttendanceLog;

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
                    if ($this->recordAttendance($deviceSn, $userId, $date, $time)) {
                        $savedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Gagal memproses absensi ADMS baris: {$line}. Error: " . $e->getMessage());
                }
            }
        }

        return $savedCount;
    }

    /**
     * Record single attendance entry.
     */
    private function recordAttendance(string $deviceSn, string $studentNis, string $date, string $time): bool
    {
        // Cari siswa berdasarkan NIS (karena USER_ID di ADMS biasanya = NIS siswa)
        $student = Student::where('nis', $studentNis)->first();
        if (!$student) {
            UnknownAttendanceLog::create([
                'device_sn' => $deviceSn,
                'nis_scanned' => $studentNis,
                'date' => $date,
                'time' => $time,
            ]);
            return false; // Skip jika siswa tidak ditemukan
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

            $newTime = Carbon::parse($date . ' ' . $time);
            $timeInChanged = false;
            $jenisAbsen = '';

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
                    
                    if ($newTime->gt($startTime)) {
                        $status = 'terlambat';
                        $lateMinutes = $startTime->diffInMinutes($newTime);
                    }
                } elseif ($scheduleInfo['is_holiday']) {
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
                    'description' => "Siswa tap masuk pada {$time} (Perangkat: {$deviceSn})",
                    'ip_address' => request()->ip(),
                ]);
                $jenisAbsen = 'Datang';
            } else {
                // DATA SUDAH ADA, TANGANI POTENSI MULTI MESIN / OUT OF ORDER
                $existingTimeIn = Carbon::parse($date . ' ' . $attendance->time_in);
                $existingTimeOut = $attendance->time_out ? Carbon::parse($date . ' ' . $attendance->time_out) : null;
                
                if ($newTime->lt($existingTimeIn)) {
                    // Tap baru LEBIH AWAL dari time_in saat ini.
                    // Geser time_in lama ke time_out (jika time_out belum ada atau time_in lama lebih akhir dari time_out)
                    if (!$existingTimeOut || $existingTimeIn->gt($existingTimeOut)) {
                        $attendance->time_out = $attendance->time_in;
                    }
                    $attendance->time_in = $time;
                    $attendance->device_sn = $deviceSn;
                    $timeInChanged = true;
                    $jenisAbsen = 'Datang';
                } elseif (!$existingTimeOut || $newTime->gt($existingTimeOut)) {
                    // Tap baru LEBIH AKHIR dari time_out saat ini (atau time_out belum ada).
                    $attendance->time_out = $time;
                    $attendance->device_sn = $deviceSn;
                    $jenisAbsen = 'Pulang';
                } else {
                    // Tap baru berada di antara time_in dan time_out saat ini (duplicate di tengah hari).
                    // Kita abaikan saja.
                    return; // exit transaction callback
                }
                
                // Kalkulasi ulang status terlambat jika time_in berubah
                if ($timeInChanged && !$scheduleInfo['is_holiday'] && count($scheduleInfo['schedules']) > 0) {
                    $firstSchedule = $scheduleInfo['schedules']->first();
                    $startTime = Carbon::parse($date . ' ' . $firstSchedule->timeSlot->start_time);
                    
                    if ($newTime->gt($startTime)) {
                        $attendance->status = 'terlambat';
                        $attendance->late_minutes = $startTime->diffInMinutes($newTime);
                    } else {
                        $attendance->status = 'hadir';
                        $attendance->late_minutes = 0;
                    }
                }
                
                $attendance->save();

                // Log Activity
                ActivityLog::create([
                    'user_id' => $student->user_id,
                    'action' => 'attendance_update',
                    'description' => "Siswa tap {$jenisAbsen} pada {$time} (Perangkat: {$deviceSn})",
                    'ip_address' => request()->ip(),
                ]);
            }
            
            // Dispatch Fonnte Notification Job
            $eventDateTime = Carbon::parse($date . ' ' . $time);
            $now = Carbon::now();
            $isTooLate = abs($now->diffInMinutes($eventDateTime)) > 120; // 2 jam toleransi
            
            if (!$isTooLate) {
                $statusText = $attendance->status === 'hadir' ? 'Tepat Waktu' : strtoupper($attendance->status);
                // Kita gunakan $jenisAbsen yang sudah ditentukan di blok atas (Datang/Pulang)
                $jamAbsen = $time;
                
                // Pesan untuk WA
                $message = "Halo Bapak/Ibu Wali dari {$student->name},\n\n";
                $message .= "Ini adalah notifikasi absensi sekolah:\n";
                $message .= "- Jenis: {$jenisAbsen}\n";
                $message .= "- Waktu: {$jamAbsen}\n";
                $message .= "- Status: {$statusText}\n";
                
                if ($attendance->status === 'terlambat' && !$attendance->time_out) {
                    $message .= "- Keterlambatan: {$attendance->late_minutes} Menit\n";
                }
                
                $message .= "\nTerima kasih,\nSistem Pusat Data SMK Salafiyah";
                
                // Dispatch WA
                if (!empty($student->parent_phone)) {
                    \App\Jobs\SendWaNotificationJob::dispatch($student->parent_phone, $message);
                }

                // Dispatch FCM Android
                $user = $student->user;
                if ($user && !empty($user->fcm_token)) {
                    $fcmTitle = "Notifikasi Presensi {$jenisAbsen}";
                    $fcmBody = "{$student->name} tap {$jenisAbsen} pada {$jamAbsen}. Status: {$statusText}.";
                    \App\Jobs\SendFcmNotificationJob::dispatch($user->fcm_token, $fcmTitle, $fcmBody);
                }
            }
        });

        return true;
    }
}
