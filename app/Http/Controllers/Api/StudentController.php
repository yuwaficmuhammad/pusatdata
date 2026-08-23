<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private StudentService $studentService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        $filters = $request->only(['status', 'search']);
        $perPage = $request->query('per_page', 15);

        $students = $this->studentService->getAllStudents($filters, $perPage);

        return $this->successResponse(
            $students->items(),
            'Data siswa berhasil diambil',
            200,
            [
                'page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage()
            ]
        );
    }

    public function store(Request $request)
    {
        $this->authorize('create', Student::class);

        $validated = $request->validate([
            'nis' => 'required|string|max:20|unique:student,nis',
            'nisn' => 'required|string|max:10|unique:student,nisn',
            'name' => 'required|string|max:100',
            'gender' => 'required|in:L,P',
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'religion' => 'required|string|max:30',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'parent_name' => 'required|string|max:100',
            'parent_phone' => 'required|string|max:20',
            'status' => 'nullable|in:active,graduated,transferred,dropped',
            'email' => 'nullable|email|unique:user,email',
            'password' => 'nullable|string|min:6',
        ]);

        try {
            $student = $this->studentService->createStudent($validated);
            return $this->successResponse($student, 'Data siswa berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }

    public function show(Student $student)
    {
        $this->authorize('view', $student);
        $student->load(['user', 'classrooms']);
        
        return $this->successResponse($student, 'Data siswa berhasil diambil');
    }

    public function update(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'nis' => 'sometimes|required|string|max:20|unique:student,nis,' . $student->id,
            'nisn' => 'sometimes|required|string|max:10|unique:student,nisn,' . $student->id,
            'name' => 'sometimes|required|string|max:100',
            'gender' => 'sometimes|required|in:L,P',
            'birth_place' => 'sometimes|required|string|max:100',
            'birth_date' => 'sometimes|required|date',
            'religion' => 'sometimes|required|string|max:30',
            'address' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string|max:20',
            'parent_name' => 'sometimes|required|string|max:100',
            'parent_phone' => 'sometimes|required|string|max:20',
            'status' => 'sometimes|required|in:active,graduated,transferred,dropped',
            'password' => 'nullable|string|min:6',
        ]);

        try {
            $updatedStudent = $this->studentService->updateStudent($student, $validated);
            return $this->successResponse($updatedStudent, 'Data siswa berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);
        
        try {
            $this->studentService->deleteStudent($student);
            return $this->successResponse(null, 'Data siswa berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }
}
