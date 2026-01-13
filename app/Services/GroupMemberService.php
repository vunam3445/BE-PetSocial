<?php

namespace App\Services;

use App\Repositories\GroupMemberRepository\GroupMemberRepository;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Group;
use App\Notifications\GroupKickedNotification;
class GroupMemberService
{
    protected $groupMemberRepository;
    public function __construct(GroupMemberRepository $groupMemberRepository)
    {
        $this->groupMemberRepository = $groupMemberRepository;
    }

    public function deleteMember(string $groupId, string $userId)
    {
        return DB::transaction(function () use ($groupId, $userId) {
            try {
                $user = User::find($userId);
                $group = Group::find($groupId);
                // 1. Xóa yêu cầu tham gia (Kéo theo xóa luôn câu trả lời do cascade)
                $this->groupMemberRepository->deleteJoinRequests($groupId, $userId);

                // 2. Xóa tư cách thành viên trong bảng group_members
                $res = $this->groupMemberRepository->deleteMember($groupId, $userId);
                // 3. Gửi thông báo (Chỉ gửi nếu xóa thành công)
                if ($res && $user && $group) {
                    $user->notify(new GroupKickedNotification($group));
                }
                return $res;
            } catch (\Exception $e) {
                throw $e;
            }
        });
    }
}
