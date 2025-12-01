<?php
namespace App\Services;
use App\Repositories\GroupRepository\GroupInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class GroupService
{
    protected $groupRepository;

    public function __construct(GroupInterface $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }
    public function createGroup(array $data)
    {
        $userId = Auth::id();
        if ($data['created_by'] != $userId) {
            throw new \Exception('Unauthorized: created_by does not match authenticated user.');
        }
        $group = $this->groupRepository->create($data);
        if($group){
            $this->groupRepository->addMemberToGroup($group->group_id, [$userId], 'admin');
        }
        return $group;
    }


    public function updateGroup($group, array $data)
    {
        return DB::transaction(function () use ($group, $data) {

            // --- XỬ LÝ AVATAR ---
            if (isset($data['avatar']) && $data['avatar']) {
                // 1. Xóa ảnh cũ (Phải cắt domain đi mới xóa được trong Storage)
                if ($group->avatar_url) {

                    // Biến đổi: http://.../storage/avatars/abc.jpg -> avatars/abc.jpg
                    $relativePath = str_replace(asset('storage/'), '', $group->avatar_url);
                    if($relativePath != 'avatars/default-avatar.webp'){
                        Storage::disk('public')->delete($relativePath);
                    }
                }

                // 2. Upload ảnh mới (lấy đường dẫn tương đối)
                $path = $data['avatar']->store('avatars', 'public');

                // 3. Lưu FULL URL vào data: http://127.0.0.1:8000/storage/avatars/...
                $data['avatar_url'] = asset('storage/' . $path);

                // 4. Xóa field file raw
                unset($data['avatar']);
            }

            // --- XỬ LÝ COVER ---
            if (isset($data['cover']) && $data['cover']) {
                // 1. Xóa cover cũ
                if ($group->cover_url) {
                    $relativePath = str_replace(asset('storage/'), '', $group->cover_url);
                    if($relativePath != 'covers/default-avatar.webp'){

                        Storage::disk('public')->delete($relativePath);
                    }
                }

                // 2. Upload cover mới
                $path = $data['cover']->store('covers', 'public');

                // 3. Lưu FULL URL
                $data['cover_url'] = asset('storage/' . $path);

                // 4. Xóa field file raw
                unset($data['cover']);
            }

            // Cập nhật vào DB
            return $this->groupRepository->update($group, $data)->fresh();
        });
    }
    public function deleteGroup($group)
    {
        return $this->groupRepository->delete($group);
    }
    public function getGroupsByUserId($userId)
    {
        return $this->groupRepository->getGroupsByUserId($userId);
    }
    public function getGroupOfUserAttended($userId)
    {
        return $this->groupRepository->getGroupOfUserAttended($userId);
    }

    public function addMemberToGroup(string $groupId, array $userIds, string $role): void
    {
        $this->groupRepository->addMemberToGroup($groupId, $userIds, $role);
    }

    public function getGroupInfo(string $groupId)
    {
        $userId = Auth::id();
        return $this->groupRepository->getGroupById($groupId, $userId);
    }

    public function getMembers(string $groupId)
    {   
        return $this->groupRepository->getMembers($groupId);
    }
}