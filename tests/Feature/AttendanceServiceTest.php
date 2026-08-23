<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActiveDay;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\ScheduleVersion;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Jobs\SendWaNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attendanceService = app(AttendanceService::class);
    }

    public function test_process_adms_push_calculates_late_correctly()
    {
        Queue::fake(); // Mencegah job benar-benar dijalankan

        // Setup Jadwal Mulai 07:00
        $date = Carbon::now()->format('Y-m-d');
        $academicYear = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $academicYear->id, 'name' => 'ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
        
        $userTeacher = User::create(['name' => 'Teacher', 'email' => 't@t.com', 'password' => bcrypt('password'), 'role' => 'homeroom_teacher']);
        $teacher = Teacher::create(['user_id' => $userTeacher->id, 'name' => 'Budi', 'phone' => '0812345678']);
        
        $classroom = Classroom::create(['name' => 'X RPL 1', 'grade' => 10, 'homeroom_teacher_id' => $teacher->id, 'academic_year_id' => $academicYear->id]);
        
        $timeSlot1 = TimeSlot::create(['name' => 'Jam ke-1', 'start_time' => '07:00', 'end_time' => '07:45', 'is_break' => false]);
        $subject = Subject::create(['name' => 'Web', 'code' => 'WEB', 'type' => 'produktif']);

        $version = ScheduleVersion::create([
            'semester_id' => $semester->id,
            'name' => 'Jadwal Normal',
            'valid_from' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'valid_until' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'is_active' => true
        ]);

        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;
        $activeDay = ActiveDay::create(['schedule_version_id' => $version->id, 'day_of_week' => $dayOfWeek, 'is_holiday' => false]);

        Schedule::create([
            'schedule_version_id' => $version->id,
            'classroom_id' => $classroom->id,
            'active_day_id' => $activeDay->id,
            'time_slot_id' => $timeSlot1->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        // Setup Siswa
        $userStudent = User::create(['name' => 'Student', 'email' => 's@s.com', 'password' => bcrypt('password'), 'role' => 'student', 'fcm_token' => 'sample_fcm_token']);
        $student = Student::create([
            'user_id' => $userStudent->id,
            'nis' => '1001',
            'nisn' => '0000001001',
            'name' => 'Andi',
            'gender' => 'L',
            'birth_place' => 'Jakarta',
            'birth_date' => '2010-01-01',
            'religion' => 'islam',
            'address' => 'Jl. Test',
            'phone' => '08123456780',
            'parent_name' => 'Budi Ayah',
            'parent_phone' => '08123456789',
            'status' => 'active'
        ]);
        
        $student->classrooms()->attach($classroom->id, [
            'joined_at' => '2026-07-01',
            'mutation_reason' => 'active'
        ]);

        // 1. Uji Tap Masuk Terlambat (Jam 07:15)
        Carbon::setTestNow(Carbon::parse("{$date} 07:15:05"));
        $rawBodyIn = "1001 {$date} 07:15:00 0 1";
        $this->attendanceService->processAdmsPush('SN123', $rawBodyIn);

        $attendance = Attendance::where('student_id', $student->id)->whereDate('date', $date)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('07:15:00', $attendance->time_in);
        $this->assertNull($attendance->time_out);
        $this->assertEquals('terlambat', $attendance->status);
        $this->assertEquals(15, $attendance->late_minutes);

        // Pastikan Job dipanggil
        Queue::assertPushed(SendWaNotificationJob::class);
        Queue::assertPushed(\App\Jobs\SendFcmNotificationJob::class);

        // 2. Uji Tap Keluar (Jam 15:00)
        Carbon::setTestNow(Carbon::parse("{$date} 15:00:05"));
        $rawBodyOut = "1001 {$date} 15:00:00 1 1";
        $this->attendanceService->processAdmsPush('SN123', $rawBodyOut);

        $attendance->refresh();
        $this->assertEquals('07:15:00', $attendance->time_in); // Waktu masuk tetap
        $this->assertEquals('15:00:00', $attendance->time_out); // Waktu pulang terisi
    }

    public function test_process_adms_push_unknown_nis_is_logged()
    {
        $date = Carbon::now()->format('Y-m-d');
        $rawBodyIn = "99999 {$date} 07:15:00 0 1";
        
        $savedCount = $this->attendanceService->processAdmsPush('SN123', $rawBodyIn);
        
        $this->assertEquals(0, $savedCount); // Data tidak disave ke tabel presensi aktif
        
        // Cek bahwa data masuk ke tabel unknown_attendance_logs
        $this->assertDatabaseHas('unknown_attendance_logs', [
            'nis_scanned' => '99999',
            'date' => $date,
            'time' => '07:15:00',
            'device_sn' => 'SN123'
        ]);
    }
}
