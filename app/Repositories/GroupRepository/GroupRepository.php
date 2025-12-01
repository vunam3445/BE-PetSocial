<?php

namespace App\Repositories\GroupRepository;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupRepository  implements GroupInterface
{
    public function create(array $data): Group
    {
        $data['avatar_url'] = url('storage/avatars/default-avatar.webp');
        $data['cover_url'] = url('storage/avatars/default-avatar.webp');
        $data['require_post_approval'] = $data['require_post_approval'] ?? false;
        return Group::create($data);
    }
    public function update(Group $group, array $data): Group
    {
        $group->update($data);
        return $group;
    }
    public function delete(Group $group): bool
    {
        DB::transaction(function () use ($group) {
            // Xóa tất cả membership
            $group->members()->detach();

            // Xóa group
            $group->delete();
        });

        return true;
    }

    public function getGroupsByUserId(string $userId): array
    {
        return Group::query()
            ->where('created_by', $userId)
            ->orWhereExists(function ($query) use ($userId) {
                $query->from('group_members as gm')
                    ->whereColumn('gm.group_id', 'groups.group_id')
                    ->where('gm.user_id', $userId)
                    ->where('gm.role', 'admin');
            })
            ->get()
            ->toArray();
    }

    public function getGroupOfUserAttended(string $userId): array
    {
        $user = User::find($userId);
        return $user->groupMemberships()
            ->wherePivot('joined_at')
            ->get()
            ->toArray();
    }
    public function addMemberToGroup(string $groupId, array $userIds, string $role): void
    {
        $group = Group::find($groupId);
        foreach ($userIds as $userId) {
            $group->members()->syncWithoutDetaching([
                $userId => [
                    'role' => $role,
                    'joined_at' => now()
                ]
            ]);
        }
    }

    public function getGroupById(string $groupId, string $userId): array
    {
        $group = Group::findOrFail($groupId);

        // Lấy membership của user trong group
        $membership = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        // Đếm tổng số thành viên đã join
        $totalMembers = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('joined_at')
            ->count();

        return [
            'group' => $group,
            'member_role' => $membership->role ?? null,
            'total_members' => $totalMembers,
        ];
    }
    public function getMembers(string $groupId, int $limit = 20)
    {
        $group = Group::findOrFail($groupId);

        // 1. Query DB: Chỉ lấy các cột cần thiết để tối ưu hiệu năng
        $members = $group->members()
            // Chỉ select cột từ bảng users
            ->select(['users.user_id', 'users.name', 'users.avatar_url'])
            // Khai báo rõ các cột muốn lấy từ bảng pivot (bảng trung gian)
            ->withPivot(['role','joined_at'])
            ->orderByPivot('joined_at', 'desc')
            ->paginate($limit);

        // 2. Transform: Format lại dữ liệu JSON trả về cho gọn
        // Hàm through() sẽ chạy qua từng item trong trang hiện tại để map lại cấu trúc
        $members->through(function ($member) {
            return [
                'user_id'    => $member->user_id,
                'name'       => $member->name,
                'avatar_url' => $member->avatar_url,
                // Lấy thông tin từ pivot và làm phẳng (flatten) nó ra
                'role'       => $member->pivot->role,
                'joined_at'  => $member->pivot->joined_at,
            ];
        });

        return $members;
    }

    public function find(string $groupId)
    {
        return Group::findOrFail($groupId);
    }
}
