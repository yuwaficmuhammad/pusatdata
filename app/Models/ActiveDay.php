<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveDay extends Model
{
    use HasFactory;

    protected $table = 'active_day';

    protected $fillable = [
        'schedule_version_id',
        'day_of_week',
        'is_holiday',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_holiday' => 'boolean',
    ];

    public function scheduleVersion()
    {
        return $this->belongsTo(ScheduleVersion::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
