<?php
/**
 * Sunny 2020/12/15 下午8:51
 * ogg sit down and start building bugs.
 * Author: Ogg <baoziyoo@gmail.com>
 */

namespace App\Models\Job\Dao\Impl;

use App\Models\BaseDao;
use App\Models\Job\Dao\JobDao;

class JobDaoImpl extends BaseDao implements JobDao
{
    protected $guarded = [];
    protected $table = 'job';
}
