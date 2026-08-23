<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentService
{
    /**
     * Get all students with optional filters.
     */
    public function getAllStudents($filters = [], $perPage = 15)
    {
        $query = Student::with('user', 'classrooms');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('nis', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('nisn', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new student and their user account.
     */
    public function createStudent(array $data): Student
    {
        DB::beginTransaction();
        try {
            // Create user account for student
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? strtolower($data['nis']) . '@smksalafiyah.sch.id', // Default email if not provided
                'password' => Hash::make($data['password'] ?? $data['nis']), // Default password is NIS
                'role' => 'student',
                'is_active' => true,
            ]);

            // Create student profile
            $student = Student::create([
                'user_id' => $user->id,
                'nis' => $data['nis'],
                'nisn' => $data['nisn'],
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'religion' => $data['religion'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'parent_name' => $data['parent_name'],
                'parent_phone' => $data['parent_phone'],
                'status' => $data['status'] ?? 'active',
            ]);

            DB::commit();
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing student.
     */
    public function updateStudent(Student $student, array $data): Student
    {
        DB::beginTransaction();
        try {
            $student->update([
                'nis' => $data['nis'] ?? $student->nis,
                'nisn' => $data['nisn'] ?? $student->nisn,
                'name' => $data['name'] ?? $student->name,
                'gender' => $data['gender'] ?? $student->gender,
                'birth_place' => $data['birth_place'] ?? $student->birth_place,
                'birth_date' => $data['birth_date'] ?? $student->birth_date,
                'religion' => $data['religion'] ?? $student->religion,
                'address' => $data['address'] ?? $student->address,
                'phone' => $data['phone'] ?? $student->phone,
                'parent_name' => $data['parent_name'] ?? $student->parent_name,
                'parent_phone' => $data['parent_phone'] ?? $student->parent_phone,
                'status' => $data['status'] ?? $student->status,
            ]);

            if (isset($data['name'])) {
                $student->user->update(['name' => $data['name']]);
            }

            if (!empty($data['password'])) {
                $student->user->update(['password' => Hash::make($data['password'])]);
            }

            DB::commit();
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a student.
     */
    public function deleteStudent(Student $student): bool
    {
        DB::beginTransaction();
        try {
            $user = $student->user;
            $student->delete();
            $user->delete(); // Also delete the user account
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
