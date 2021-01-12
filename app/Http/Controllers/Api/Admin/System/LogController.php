<?php

namespace App\Http\Controllers\Api\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Log\Service\LogService;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function search(Request $request)
    {
        $conditions = $request->get('conditions', '[]');
        $conditions = json_decode($conditions, true);

        return $this->getLogService()->searchByPagination($conditions, []);
    }

    private function getLogService(): LogService
    {
        return $this->getService('Log:Log');
    }
}
