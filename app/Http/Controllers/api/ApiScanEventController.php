<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\QRCode;
use App\Models\QRCodeLog;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiScanEventController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function scanAttendance(Request $request, $id)
    {
        $request->validate([
            'qrcode_data' => 'required|string',
        ]);

        // Langsung return response dari service
        return $this->attendanceService->scanAttendance($id, $request->qrcode_data, $request->user()->id);
    }

    public function scanIdentityCheck(Request $request, $id)
    {
        $request->validate([
            'qrcode_data' => 'required|string',
        ]);

        // Langsung return response dari service
        return $this->attendanceService->scanIdentityCheck($id, $request->qrcode_data, $request->user()->id);
    }
}
