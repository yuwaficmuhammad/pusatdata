<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(private ScheduleService $scheduleService)
    {
    }

    /**
     * Dapatkan jadwal hari ini (atau tanggal tertentu) untuk suatu kelas.
     */
    public function today(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id' => 'required|exists:classroom,id',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', 422, $validator->errors()->toArray());
        }

        $date = $request->input('date', date('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $scheduleData = $this->scheduleService->getTodaySchedule($classroomId, $date);

        return $this->successResponse($scheduleData, 'Jadwal berhasil diambil.');
    }
}
