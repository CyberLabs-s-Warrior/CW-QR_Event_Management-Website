<?php

namespace App\Services\Logs;

use App\Models\UserLog;

class UsersLogService
{
  public function returnAll()
  {
    return UserLog::all();
  }
}
