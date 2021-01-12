<?php
/**
 * Sunny 2020/12/15 上午9:38
 * ogg sit down and start building bugs.
 * Author: Ogg <baoziyoo@gmail.com>
 */

namespace App\Models\Log\Service;

use App\Models\BaseServiceInterface;
use App\Models\Log\Service\Impl\LogServiceImpl;

interface LogService extends BaseServiceInterface
{
    public function create($message, $data = [], $level = LogServiceImpl::TRACE);

    public function createTrace($message, $data = [], $level = LogServiceImpl::TRACE);

    public function createDebug($message, $data = [], $level = LogServiceImpl::DEBUG);

    public function createInfo($message, $data = [], $level = LogServiceImpl::INFO);

    public function createWarn($message, $data = [], $level = LogServiceImpl::WARN);

    public function createError($message, $data = [], $level = LogServiceImpl::ERROR);

    public function createOff($message, $data = [], $level = LogServiceImpl::OFF);

    public function searchByPagination($conditions, $orderBy): array;
}
