<?php

namespace App\Models\User\Service;

use App\Models\BaseModel;
use App\Models\Log\Service\LogService;
use App\Models\User\Dao\GroupDao;
use App\Models\User\Validator\GroupValidator;
use App\Toolkit\ArrayTools;

class GroupService extends BaseModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->dao = $this->getGroupDao();
    }

    public function createGroup($data): bool
    {
        $validator = new GroupValidator();
        if (!$validator->scene('create')->check($data)) {
            throw new \InvalidArgumentException($validator->getError());
        }

        if ($this->getByName($data['name'])) {
            throw new \InvalidArgumentException('用户组已存在');
        }

        $data['createUserId'] = $this->getCurrentUser()->getId();
        $data['updateUserId'] = $this->getCurrentUser()->getId();

        $this->getGroupDao()->create($data);
        $this->getLogService()->createTrace('创建:用户组'.$data['name'], $data);

        return true;
    }

    public function updateGroup($id, $data): bool
    {
        $validator = new GroupValidator();
        if (!$validator->scene('update')->check($data)) {
            throw new \InvalidArgumentException($validator->getError());
        }

        $groupInfo = $this->getById($id);
        if (empty($groupInfo)) {
            throw new \InvalidArgumentException('用户组不存在');
        }

        $this->checkName($id, $data['name']);

        $data = ArrayTools::parts($data, ['name', 'rules']);
        $data['updateUserId'] = $this->getCurrentUser()->getId();

        $this->getGroupDao()->where('id', $id)->update($data);
        $this->getLogService()->createTrace('修改:用户组'.$id, $data);

        return true;
    }

    public function deleteGroup($id): bool
    {
        if (!$this->getById($id)) {
            throw new \InvalidArgumentException('用户组不存在');
        }

        $this->getGroupDao()->where('id', $id)->delete();
        $this->getLogService()->createTrace('删除:用户组', $id);

        return true;
    }

    public function searchByPagination($conditions, $orderBy): array
    {
        [$offset, $limit] = $this->getOffsetAndLimit();

        $conditions = $this->prepareConditions($conditions);

        $data = $this->search($conditions, $orderBy, $offset, $limit);
        $count = $this->count($conditions);

        return [
            'data' => $data,
            'paging' => [
                'total' => $count,
                'offset' => $offset,
                'limit' => $limit,
            ],
        ];
    }

    public function getGroup($id)
    {
        $groupInfo = $this->getById($id);

        if (!$groupInfo) {
            throw new \InvalidArgumentException('用户组不存在');
        }

        return $groupInfo;
    }

    protected function prepareConditions($conditions): array
    {
        $newConditions = [];

        if (!empty($conditions['name'])) {
            $newConditions[] = ['name', 'like', '%'.$conditions['name'].'%'];
        }

        return $newConditions;
    }

    protected function checkName($id, $name): bool
    {
        $group = $this->getByName($name);

        if (!empty($group) && $group['id'] != $id) {
            throw new \InvalidArgumentException('用户组已存在');
        }

        return true;
    }

    protected function getByName($name)
    {
        return $this->getGroupDao()->where(['name' => $name])->first()->toArray();
    }

    private function getGroupDao(): GroupDao
    {
        return $this->getDao('User:GroupDao');
    }

    private function getLogService(): LogService
    {
        return $this->getService('Log:LogService');
    }
}
