<?php
namespace App\Repositories\GroupMemberRepository;

use App\Models\GroupMember;
use App\Repositories\Base\BaseRepository;
use App\Models\GroupJoinRequest;
class GroupMemberRepository extends BaseRepository implements GroupMemberInterface{

    public function __construct(GroupMember $model)
    {
        return parent::__construct($model);
    }
    public function deleteMember(string $groupId, string $userId)
    {
        $res = GroupMember::where('group_id', $groupId)
                            ->where('user_id', $userId)
                            ->delete();
        return $res;
    }
    public function deleteJoinRequests(string $groupId, string $userId)
    {
        // Vì có onDelete('cascade') ở database nên xóa Request sẽ tự xóa Answer
        return GroupJoinRequest::where('group_id', $groupId)
                                ->where('user_id', $userId)
                                ->delete();
    }
}