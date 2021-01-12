<?php
/**
 * Sunny 2020/12/15 上午9:38
 * ogg sit down and start building bugs.
 * Author: Ogg <baoziyoo@gmail.com>
 */

namespace App\Models\Log\Service\Impl;

use App\Models\BaseService;
use App\Models\Log\Dao\LogDao;
use App\Models\Log\Service\LogService;

class LogServiceImpl extends BaseService implements LogService
{
    public const TRACE = 1;
    public const DEBUG = 2;
    public const INFO = 3;
    public const WARN = 4;
    public const ERROR = 5;
    public const OFF = 6;

    public static $level = [
        // 记录
        1 => 'Trace',
        // 排查问题
        2 => 'Debug',
        // 运行产生的事件
        3 => 'Info',
        // 预料之外的情况
        4 => 'Warn',
        // 运行异常
        5 => 'Error',
        // 提前结束
        6 => 'Off',
    ];

    public function create($message, $data = [], $level = self::TRACE)
    {
        $data = [
            'userId' => $this->getCurrentUser()->getId(),
            'message' => $message,
            'data' => json_encode($data),
            'ip' => request()->ip(),
            'level' => $level,
        ];

        $logCount = $this->count([]);
        $data['id'] = $logCount + 1;

        return $this->getLogDao()->create($data);
    }

    public function createTrace($message, $data = [], $level = self::TRACE)
    {
        return $this->create($message, $data, $level);
    }

    public function createDebug($message, $data = [], $level = self::DEBUG)
    {
        return $this->create($message, $data, $level);
    }

    public function createInfo($message, $data = [], $level = self::INFO)
    {
        return $this->create($message, $data, $level);
    }

    public function createWarn($message, $data = [], $level = self::WARN)
    {
        return $this->create($message, $data, $level);
    }

    public function createError($message, $data = [], $level = self::ERROR)
    {
        return $this->create($message, $data, $level);
    }

    public function createOff($message, $data = [], $level = self::OFF)
    {
        return $this->create($message, $data, $level);
    }

    public function searchByPagination($conditions, $orderBy): array
    {
        $conditions = $this->prepareConditions($conditions);

        $logData = $this->getLogDao()->searchByPagination($conditions, $orderBy);

        return $this->filterData($logData);
    }

    protected function filterData($data): array
    {
        foreach ($data['data'] as &$value) {
            if (!empty($value['level'])) {
                $value['level'] = self::$level[$value['level']];
            }
        }

        return $data;
    }

    protected function prepareConditions($conditions): array
    {
        $newConditions = [];

        if (!empty($conditions['message'])) {
            $newConditions[] = ['message', 'like', '%'.$conditions['message'].'%'];
        }
        if (!empty($conditions['level'])) {
            $newConditions[] = ['level', '=', $conditions['level']];
        }

        return $newConditions;
    }

    private function getLogDao(): LogDao
    {
        return $this->getDao('Log:Log');
    }
}
