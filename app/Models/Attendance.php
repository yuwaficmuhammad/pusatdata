<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'schedule_version_id',
        'date',
        'time_in',
        'time_out',
        'status',
        'late_minutes',
        'device_sn',
    ];

    protected $casts = [
        'date' => 'date',
        'late_minutes' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function scheduleVersion()
    {
        return $this->belongsTo(ScheduleVersion::class);
    }
}
