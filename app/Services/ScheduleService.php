<?php

namespace App\Services;

use App\Models\ScheduleVersion;
use App\Models\Schedule;
use App\Models\AcademicCalendar;
use Carbon\Carbon;
use Exception;

class ScheduleService
{
    /**
     * Dapatkan versi jadwal aktif berdasarkan tanggal.
     */
    public function getActiveVersion(string $date): ?ScheduleVersion
    {
        return ScheduleVersion::where('valid_from', '<=', $date)
            ->where('valid_until', '>=', $date)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Dapatkan jadwal harian untuk kelas tertentu pada tanggal tertentu.
     */
    public function getTodaySchedule(int $classroomId, string $date)
    {
        // 1. Cek apakah hari libur di Kalender Akademik
        $holiday = AcademicCalendar::whereDate('date', $date)->where('type', 'holiday')->first();
        if ($holiday) {
            return [
                'is_holiday' => true,
                'holiday_reason' => $holiday->description,
                'schedules' => collect([])
            ];
        }

        // 2. Dapatkan versi jadwal aktif
        $version = $this->getActiveVersion($date);
        if (!$version) {
            return [
                'is_holiday' => false,
                'holiday_reason' => null,
                'schedules' => collect([]),
                'error' => 'Tidak ada versi jadwal aktif untuk tanggal ini.'
            ];
        }

        // 3. Tentukan day_of_week (1 = Senin, 7 = Minggu)
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

        // 4. Cek active_day
        $activeDay = $version->activeDays()->where('day_of_week', $dayOfWeek)->first();
        if (!$activeDay || $activeDay->is_holiday) {
            return [
                'is_holiday' => true,
                'holiday_reason' => 'Hari libur mingguan (Active Day)',
                'schedules' => collect([])
            ];
        }

        // 5. Eager load schedule untuk mencegah N+1
        $schedules = Schedule::with(['timeSlot', 'subject', 'teacher'])
            ->where('schedule_version_id', $version->id)
            ->where('active_day_id', $activeDay->id)
            ->where('classroom_id', $classroomId)
            ->get()
            ->sortBy(function ($schedule) {
                return $schedule->timeSlot->start_time;
            })
            ->values();

        return [
            'is_holiday' => false,
            'holiday_reason' => null,
            'schedules' => $schedules
        ];
    }
}
