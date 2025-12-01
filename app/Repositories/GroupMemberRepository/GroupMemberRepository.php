<?php
namespace App\Repositories\GroupMemberRepository;

use App\Models\GroupMember;
use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class GroupMemberRepository extends BaseRepository implements GroupMemberInterface{

    public function __construct(GroupMember $model)
    {
        return parent::__construct($model);
    }
}