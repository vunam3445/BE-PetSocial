<?php

namespace App\Repositories\MembershipQuestion;

use App\Repositories\Base\BaseRepository;
use App\Models\GroupJoinQuestion;
use App\Models\GroupJoinAnswer;

class MembershipRepository extends BaseRepository implements MembershipInterface
{
    public function __construct(GroupJoinQuestion $model)
    {
        parent::__construct($model);
    }
    public function createJoinAnswer(array $data)
    {
        return GroupJoinAnswer::create($data);
    }
}
