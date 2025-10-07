<?php

namespace App\Services\Logs;

use App\Models\QRCodeLog;

class QRCodeLogService
{
  public function returnAll()
  {
    return QRCodeLog::all();
  }
}
