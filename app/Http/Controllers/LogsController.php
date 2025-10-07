<?php

namespace App\Http\Controllers;

use App\Models\QRCode;
use App\Models\QRCodeLog;
use App\Services\Logs\QRCodeLogService;
use App\Services\Logs\UsersLog;
use App\Services\Logs\UsersLogService;
use Illuminate\Http\Request;

class LogsController extends Controller
{
  protected $usersLogService;
  protected $qrcodeLogService;

  public function __construct(UsersLogService $usersLogService, QRCodeLogService $qrcodeLogService)
  {
    $this->usersLogService = $usersLogService;
    $this->qrcodeLogService = $qrcodeLogService;
  }

  public function userLog()
  {
    $userlog = $this->usersLogService->returnAll();
    return view('admin.logs.user_log', compact('userlog'));
  }

  public function qrLogs()
  {
    $qrcodelog = $this->qrcodeLogService->returnAll();
    return view('admin.logs.qrcode_log', compact('qrcodelog'));
  }
}
