<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentClassroom extends Pivot
{
    protected $table = 'student_classroom';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'joined_at',
        'left_at',
        'mutation_reason',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'left_at' => 'date',
    ];
}
