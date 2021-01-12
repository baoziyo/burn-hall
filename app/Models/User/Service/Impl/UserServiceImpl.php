<?php
/**
 * Sunny 2020/12/15 下午8:51
 * ogg sit down and start building bugs.
 * Author: Ogg <baoziyoo@gmail.com>
 */

namespace App\Models\User\Service\Impl;

use App\Models\BaseService;
use App\Models\Log\Service\LogService;
use App\Models\User\Dao\UserDao;
use App\Models\User\Service\GroupService;
use App\Models\User\Service\MenuService;
use App\Models\User\Service\UserService;
use App\Models\User\Validator\UserValidator;
use App\Toolkit\ArrayTools;
use App\Toolkit\CharTools;
use Illuminate\Support\Facades\Hash;

class UserServiceImpl extends BaseService implements UserService
{
    // 是否为管理员
    public const IS_ADMIN_TRUE = 1;
    public const IS_ADMIN_FALSE = 0;

    // 状态
    public const STATUS_TRUE = 1;
    public const STATUS_FALSE = 0;

    public function createUser($data): bool
    {
        $validator = new UserValidator();
        if (!$validator->scene('create')->check($data)) {
            throw new \InvalidArgumentException($validator->getError());
        }
        if ($this->getByName($data['name'])) {
            throw new \InvalidArgumentException('用户名称已存在');
        }

        $data['salt'] = CharTools::getRandChar(16);
        $data['guid'] = CharTools::generateGuid();
        $data['password'] = Hash::make(sha1(md5($data['salt'].config('app.salt').$data['password']).$data['salt']));
        $data['isAdmin'] = self::IS_ADMIN_FALSE;
        $data['status'] = self::STATUS_TRUE;
        $data['createUserId'] = $this->getCurrentUser()->getId();
        $data['updateUserId'] = $this->getCurrentUser()->getId();

        $this->getUserDao()->create($data);
        $this->getLogService()->createTrace('创建:用户'.$data['name'], $data);

        return true;
    }

    public function getUserByGuid($guid): array
    {
        $userInfo = $this->getUserDao()->where(['guid' => $guid])->first();

        if (empty($userInfo)) {
            throw new \InvalidArgumentException('用户不存在');
        }

        $userData = $userInfo->toArray();

        return $this->filterData($userData);
    }

    public function updateUser($guid, $data): bool
    {
        $validator = new UserValidator();
        if (!$validator->scene('update')->check($data)) {
            throw new \InvalidArgumentException($validator->getError());
        }

        $this->getUserByGuid($guid);

        $this->checkName($guid, $data['name']);

        $data = ArrayTools::parts($data, ['name', 'email', 'password', 'salt', 'group', 'status']);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make(sha1(md5($data['salt'].config('app.salt').$data['password']).$data['salt']));
        }
        $data['updateUserId'] = $this->getCurrentUser()->getId();

        $this->getUserDao()->where('guid', $guid)->update($data);
        $this->getLogService()->createTrace('修改:用户'.$guid, $data);

        return true;
    }

    public function deleteUser($guid): bool
    {
        $this->getUserDao()->where('guid', $guid)->delete();
        $this->getLogService()->createTrace('删除:用户', $guid);

        return true;
    }

    public function getUserjurisdiction($guid): array
    {
        $userInfo = $this->getUserByGuid($guid);
        $groupInfo = $this->getGroupService()->get($userInfo['group']);

        return $this->getMenuService()->getUserMenu($groupInfo['rules']);
    }

    public function modify($guid, $type, $value): bool
    {
        if (!in_array($type, ['status', 'a'])) {
            throw new \InvalidArgumentException('xxxx');
        }

        if ('status' === $type && !in_array($value, [self::STATUS_FALSE, self::STATUS_TRUE])) {
            throw new \InvalidArgumentException('xxxx');
        }

        $result = $this->getUserDao()->where('guid', $guid)->update([$type => $value]);
        $this->getLogService()->createTrace('修改状态:用户'.$guid, [$type => $value]);

        return $result;
    }

    public function searchByPagination($conditions, $orderBy): array
    {
        $conditions = $this->prepareConditions($conditions);

        $userData = $this->getUserDao()->searchByPagination($conditions, $orderBy);

        return $this->filterData($userData);
    }

    public function loginUser($data): array
    {
        $validator = new UserValidator();
        if (!$validator->scene('login')->check($data)) {
            throw new \InvalidArgumentException($validator->getError());
        }

        $userInfo = $this->getByName($data['name']);
        if (empty($userInfo)) {
            throw new \InvalidArgumentException('用户不存在');
        }

        $isCheck = Hash::check(sha1(md5($userInfo['salt'].config('app.salt').$data['password']).$userInfo['salt']), $userInfo['password']);
        if (!$isCheck) {
            throw new \InvalidArgumentException('用户名称不存在或密码有误');
        }
        if (self::IS_ADMIN_FALSE == $userInfo['isAdmin'] || self::STATUS_FALSE == $userInfo['status']) {
            throw new \InvalidArgumentException('用户被关闭或非管理员用户');
        }

        return $userInfo;
    }

    protected function filterData($data): array
    {
        $gruopInfo = $this->getGroupService()->getAll();
        $groupIds = ArrayTools::index($gruopInfo, 'id');

        if (empty($data['data'])) {
            if (!empty($data['group'])) {
                $data['groupName'] = !empty($groupIds[$data['group']]) ? $groupIds[$data['group']]['name'] : '未分组';
            }
        } else {
            foreach ($data['data'] as &$value) {
                if (!empty($groupIds[$value['group']])) {
                    $value['groupName'] = $groupIds[$value['group']]['name'];
                }
            }
        }

        return $data;
    }

    protected function checkName($guid, $name): bool
    {
        $group = $this->getByName($name);

        if (!empty($group) && $group['guid'] != $guid) {
            throw new \InvalidArgumentException('用户名称已存在');
        }

        return true;
    }

    protected function getByName($name): array
    {
        $userData = $this->getUserDao()->where(['name' => $name])->first();

        return $userData ? $userData->toArray() : [];
    }

    protected function prepareConditions($conditions): array
    {
        $newConditions = [];

        if (!empty($conditions['name'])) {
            $newConditions[] = ['name', 'like', '%'.$conditions['name'].'%'];
        }

        return $newConditions;
    }

    private function getUserDao(): UserDao
    {
        return $this->getDao('User:User');
    }

    private function getLogService(): LogService
    {
        return $this->getService('Log:Log');
    }

    private function getMenuService(): MenuService
    {
        return $this->getService('User:Menu');
    }

    private function getGroupService(): GroupService
    {
        return $this->getService('User:Group');
    }
}
