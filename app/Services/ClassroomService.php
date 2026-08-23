<?php

namespace App\Services;

use App\Models\Classroom;
use Illuminate\Support\Facades\DB;

class ClassroomService
{
    /**
     * Get all classrooms.
     */
    public function getAllClassrooms($filters = [], $perPage = 15)
    {
        $query = Classroom::with(['homeroomTeacher', 'academicYear']);

        if (!empty($filters['grade'])) {
            $query->where('grade', $filters['grade']);
        }
        
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new classroom.
     */
    public function createClassroom(array $data): Classroom
    {
        return Classroom::create($data);
    }

    /**
     * Update an existing classroom.
     */
    public function updateClassroom(Classroom $classroom, array $data): Classroom
    {
        $classroom->update($data);
        return $classroom;
    }

    /**
     * Delete a classroom.
     */
    public function deleteClassroom(Classroom $classroom): bool
    {
        return $classroom->delete();
    }
}
