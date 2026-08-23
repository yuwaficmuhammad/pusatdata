<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedule';

    protected $fillable = [
        'schedule_version_id',
        'classroom_id',
        'active_day_id',
        'time_slot_id',
        'subject_id',
        'teacher_id',
    ];

    public function scheduleVersion()
    {
        return $this->belongsTo(ScheduleVersion::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function activeDay()
    {
        return $this->belongsTo(ActiveDay::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
