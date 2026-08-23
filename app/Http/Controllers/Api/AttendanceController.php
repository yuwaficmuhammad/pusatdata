<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService)
    {
    }

    /**
     * Webhook untuk mesin ADMS Solution X902.
     * Menerima request GET untuk handshake dan POST untuk push data.
     */
    public function push(Request $request)
    {
        // 1. ADMS Server Validation (Auth)
        $sn = $request->query('SN');
        
        // Cek jika SN valid (contoh statis, bisa diganti dinamis dari DB mesin)
        if (!$sn) {
            return response('ERROR: No SN', 400)->header('Content-Type', 'text/plain');
        }

        // 2. Handshake / Init
        // Jika request GET, mesin biasanya hanya nge-ping atau init.
        if ($request->isMethod('get')) {
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        // 3. Push Data (POST)
        $table = $request->query('table');
        if ($table === 'ATTLOG') {
            $rawBody = $request->getContent();
            Log::info("ADMS Push [{$sn}]: " . $rawBody);
            
            // Proses raw text ke AttendanceService
            $count = $this->attendanceService->processAdmsPush($sn, $rawBody);

            // Response wajib format ADMS: "OK: [jumlah]"
            return response("OK: {$count}", 200)->header('Content-Type', 'text/plain');
        }

        // Response default jika table bukan ATTLOG (misal OPERLOG)
        return response('OK: 0', 200)->header('Content-Type', 'text/plain');
    }
}
