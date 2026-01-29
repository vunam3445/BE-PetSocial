<?php

namespace App\Repositories\GroupRepository;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function getGroupsByUserId(string $userId, int $perPage = 10)
    {
        return Group::query()
            ->where('created_by', $userId)
            ->orWhereExists(function ($query) use ($userId) {
                $query->from('group_members as gm')
                    ->whereColumn('gm.group_id', 'groups.group_id')
                    ->where('gm.user_id', $userId)
                    ->where('gm.role', 'admin');
            })
            ->orderBy('created_at', 'desc') // Nên có orderBy khi phân trang
            ->paginate($perPage); // Trả về LengthAwarePaginator
    }

    public function getGroupOfUserAttended(string $userId, int $perPage = 10)
    {
        $user = User::find($userId);

        if (!$user) {
            return null; // Hoặc ném ngoại lệ tùy logic của bạn
        }

        // Với quan hệ BelongsToMany, paginate sẽ tự xử lý
        return $user->groupMemberships()
            ->wherePivot('role', 'member')
            ->orderBy('group_members.joined_at', 'desc')
            ->paginate($perPage)
            ->through(function ($group) {
            return $group->makeHidden('pivot');
        });
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
        $postClass = \App\Models\Post::class;
        /** 1. Lấy membership */
        $membership = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        /** 2. Đếm tổng thành viên */
        $totalMembers = DB::table('group_members')
            ->where('group_id', $groupId)
            ->count();

        /** 3. Kiểm tra join request của user hiện tại */
        $joinRequest = DB::table('group_join_requests')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        /** 🔥 4. Đếm số bài viết cần duyệt (status = 'pending') */
        $pendingPostsCount = DB::table('posts')
            ->where('group_id', $groupId)
            ->where('status', 'pending')
            ->count();

        /** 🔥 5. Đếm số yêu cầu tham gia đang chờ (status = 'pending') */
        $pendingJoinRequestsCount = DB::table('group_join_requests')
            ->where('group_id', $groupId)
            ->where('status', 'pending')
            ->count();

        /** 🔥 6. Đếm số bài viết bị báo cáo (Dùng join hoặc subquery) 
         * Lấy số lượng bài viết độc nhất trong group này đang có ít nhất 1 report
         */
        $reportedPostsCount = DB::table('posts')
            ->where('group_id', $groupId)
            ->whereExists(function ($query) use ($postClass) {
                $query->select(DB::raw(1))
                    ->from('reports')
                    // So khớp id bài viết với reportable_id
                    ->whereRaw('reports.reportable_id = posts.post_id')
                    // Bắt buộc kiểm tra đúng loại là Post
                    ->where('reports.reportable_type', $postClass)
                    ->where('reports.status', 'pending');
            })
            ->count();
        return [
            'group' => $group,
            'member_role' => $membership->role ?? null,
            'total_members' => $totalMembers,
            'has_join_request' => $joinRequest !== null,
            'join_request_status' => $joinRequest->status ?? null,

            // CÁC THÔNG SỐ MỚI CHO ADMIN
            'pending_posts_count' => $pendingPostsCount,
            'pending_join_requests_count' => $pendingJoinRequestsCount,
            'reported_posts_count' => $reportedPostsCount,
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
            ->withPivot(['role', 'joined_at'])
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
