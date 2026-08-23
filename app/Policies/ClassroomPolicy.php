<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view the list of classrooms (e.g. for dropdowns)
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'homeroom_teacher') {
            $teacher = $user->teacher;
            return $teacher && $classroom->homeroom_teacher_id === $teacher->id;
        }

        if ($user->role === 'student') {
            $student = $user->student;
            if (!$student) return false;
            
            return $student->classrooms()
                ->where('classroom.id', $classroom->id)
                ->wherePivot('left_at', null)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Classroom $classroom): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->role === 'admin';
    }
}
