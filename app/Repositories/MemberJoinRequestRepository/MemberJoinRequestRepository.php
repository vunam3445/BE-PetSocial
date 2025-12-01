<?php

namespace App\Repositories\MemberJoinRequestRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\GroupJoinRequest;

class MemberJoinRequestRepository extends BaseRepository implements MemberJoinRequestInterface
{
    protected $model;
    public function __construct(GroupJoinRequest $model)
    {
        return parent::__construct($model);
    }

    public function deleteRequest(string $groupId, string $userId): bool
    {
        $request = $this->model
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        if (! $request) {
            return false;
        }

        return $request->delete();
    }
}
