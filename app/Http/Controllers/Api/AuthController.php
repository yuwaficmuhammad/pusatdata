<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService)
    {
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $result = $this->authService->login($validated);
            
            return $this->successResponse([
                'user' => $this->formatUser($result['user']),
                'token' => $result['token'],
            ], 'Login berhasil');
            
        } catch (ValidationException $e) {
            return $this->errorResponse('Validasi gagal', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return $this->successResponse(null, 'Logout berhasil');
    }

    public function me(Request $request)
    {
        return $this->successResponse($this->formatUser($request->user()), 'Data user aktif berhasil diambil');
    }
    
    private function formatUser($user)
    {
        $user->load('student.classrooms');
        
        $data = $user->toArray();
        $data['classroom_name'] = null;
        
        if ($user->role === 'student' && $user->student) {
            $activeClassroom = $user->student->classrooms->first();
            if ($activeClassroom) {
                $data['classroom_name'] = $activeClassroom->name;
            }
        }
        
        return $data;
    }
}
