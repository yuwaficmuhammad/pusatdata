<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleVersion extends Model
{
    use HasFactory;

    protected $table = 'schedule_version';

    protected $fillable = [
        'semester_id',
        'name',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function activeDays()
    {
        return $this->hasMany(ActiveDay::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
