<?php

namespace Tests\Feature;

use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use App\Models\ActiveDay;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\ScheduleVersion;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScheduleService $scheduleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduleService = app(ScheduleService::class);
    }

    public function test_get_today_schedule_returns_holiday_if_in_calendar()
    {
        $academicYear = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $academicYear->id, 'name' => 'ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
        
        $date = '2026-08-17'; // Hari kemerdekaan

        AcademicCalendar::create([
            'semester_id' => $semester->id,
            'date' => $date,
            'type' => 'holiday',
            'description' => 'Hari Kemerdekaan RI'
        ]);

        $result = $this->scheduleService->getTodaySchedule(1, $date);

        $this->assertTrue($result['is_holiday']);
        $this->assertEquals('Hari Kemerdekaan RI', $result['holiday_reason']);
        $this->assertEmpty($result['schedules']);
    }

    public function test_get_today_schedule_returns_correct_schedules()
    {
        // 1. Setup Data Master
        $academicYear = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $academicYear->id, 'name' => 'ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
        
        $user = User::create(['name' => 'Teacher', 'email' => 'teacher@test.com', 'password' => bcrypt('password'), 'role' => 'homeroom_teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'name' => 'Budi', 'phone' => '0812345678']);
        
        $classroom = Classroom::create(['name' => 'X RPL 1', 'grade' => 10, 'homeroom_teacher_id' => $teacher->id, 'academic_year_id' => $academicYear->id]);
        $subject = Subject::create(['name' => 'Pemrograman Web', 'code' => 'WEB01', 'type' => 'produktif']);
        
        $timeSlot1 = TimeSlot::create(['name' => 'Jam ke-1', 'start_time' => '07:00', 'end_time' => '07:45', 'is_break' => false]);
        $timeSlot2 = TimeSlot::create(['name' => 'Jam ke-2', 'start_time' => '07:45', 'end_time' => '08:30', 'is_break' => false]);

        // 2. Setup Version
        $date = Carbon::now()->format('Y-m-d');
        $version = ScheduleVersion::create([
            'semester_id' => $semester->id,
            'name' => 'Jadwal Normal',
            'valid_from' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'valid_until' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'is_active' => true
        ]);

        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;
        $activeDay = ActiveDay::create(['schedule_version_id' => $version->id, 'day_of_week' => $dayOfWeek, 'is_holiday' => false]);

        // 3. Setup Schedule
        Schedule::create([
            'schedule_version_id' => $version->id,
            'classroom_id' => $classroom->id,
            'active_day_id' => $activeDay->id,
            'time_slot_id' => $timeSlot1->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        
        Schedule::create([
            'schedule_version_id' => $version->id,
            'classroom_id' => $classroom->id,
            'active_day_id' => $activeDay->id,
            'time_slot_id' => $timeSlot2->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        // 4. Test execution
        $result = $this->scheduleService->getTodaySchedule($classroom->id, $date);

        $this->assertFalse($result['is_holiday']);
        $this->assertCount(2, $result['schedules']);
        
        // Memastikan tersortir berdasarkan start_time
        $this->assertEquals('07:00', $result['schedules'][0]->timeSlot->start_time);
        $this->assertEquals('07:45', $result['schedules'][1]->timeSlot->start_time);
    }
}
