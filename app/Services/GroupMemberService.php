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

                // 1. Kiểm tra quyền hạn của user trong nhóm này
                // Giả sử bảng group_members có cột 'role'
                $member = DB::table('group_members')
                    ->where('group_id', $groupId)
                    ->where('user_id', $userId)
                    ->first();

                if (!$member) {
                    throw new \Exception("Thành viên không tồn tại trong nhóm.");
                }

                // KIỂM TRA: Nếu là 'admin' hoặc 'owner' thì không cho phép xóa
                // Bạn có thể tùy chỉnh các role bị cấm xóa ở đây
                if (in_array($member->role, ['admin', 'owner'])) {
                    throw new \Exception("Không thể xóa người quản trị hoặc chủ sở hữu nhóm.");
                }

                // 2. Xóa yêu cầu tham gia
                $this->groupMemberRepository->deleteJoinRequests($groupId, $userId);

                // 3. Xóa tư cách thành viên
                $res = $this->groupMemberRepository->deleteMember($groupId, $userId);

                // 4. Gửi thông báo
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
