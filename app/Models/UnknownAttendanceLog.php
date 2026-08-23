<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnknownAttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_sn',
        'nis_scanned',
        'date',
        'time',
    ];
}
