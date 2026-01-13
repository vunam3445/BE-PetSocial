<?php
namespace App\Repositories\MemberJoinRequestRepository;

use App\Repositories\Base\BaseInterface;

interface MemberJoinRequestInterface extends BaseInterface{
    public function deleteRequest(string $groupId, string $userId);
    public function getJoinRequests(string $groupId, int $limit=20);
}