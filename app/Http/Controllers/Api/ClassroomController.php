<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Services\ClassroomService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private ClassroomService $classroomService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Classroom::class);

        $filters = $request->only(['grade', 'academic_year_id']);
        $perPage = $request->query('per_page', 15);

        $classrooms = $this->classroomService->getAllClassrooms($filters, $perPage);

        return $this->successResponse(
            $classrooms->items(),
            'Data kelas berhasil diambil',
            200,
            [
                'page' => $classrooms->currentPage(),
                'per_page' => $classrooms->perPage(),
                'total' => $classrooms->total(),
                'last_page' => $classrooms->lastPage()
            ]
        );
    }

    public function store(Request $request)
    {
        $this->authorize('create', Classroom::class);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'grade' => 'required|integer|min:1',
            'homeroom_teacher_id' => 'required|exists:teacher,id',
            'academic_year_id' => 'required|exists:academic_year,id',
        ]);

        try {
            $classroom = $this->classroomService->createClassroom($validated);
            return $this->successResponse($classroom, 'Data kelas berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }

    public function show(Classroom $classroom)
    {
        $this->authorize('view', $classroom);
        $classroom->load(['homeroomTeacher', 'academicYear', 'students']);
        
        return $this->successResponse($classroom, 'Data kelas berhasil diambil');
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorize('update', $classroom);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'grade' => 'sometimes|required|integer|min:1',
            'homeroom_teacher_id' => 'sometimes|required|exists:teacher,id',
            'academic_year_id' => 'sometimes|required|exists:academic_year,id',
        ]);

        try {
            $updatedClassroom = $this->classroomService->updateClassroom($classroom, $validated);
            return $this->successResponse($updatedClassroom, 'Data kelas berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Classroom $classroom)
    {
        $this->authorize('delete', $classroom);
        
        try {
            $this->classroomService->deleteClassroom($classroom);
            return $this->successResponse(null, 'Data kelas berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }
}
