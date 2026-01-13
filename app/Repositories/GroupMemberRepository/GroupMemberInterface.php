<?php
namespace App\Repositories\GroupMemberRepository;

use App\Repositories\Base\BaseInterface;

interface GroupMemberInterface extends BaseInterface{
    public function deleteMember(string $groupId, string $userId);
    public function deleteJoinRequests(string $groupId, string $userId);
}