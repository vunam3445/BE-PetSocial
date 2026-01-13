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
        if ($group) {
            $this->groupRepository->addMemberToGroup($group->group_id, [$userId], 'admin');
        }
        return $group;
    }


public function updateGroup($group, array $data)
{
    return DB::transaction(function () use ($group, $data) {

        // helper nhỏ trong scope: kiểm tra file có phải default không
        $isDefaultFile = function (?string $url, string $defaultFilename): bool {
            if (!$url) return false;
            // Lấy phần path của URL rồi lấy basename để so sánh chính xác
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) return false;
            $basename = basename($path);
            return $basename === $defaultFilename;
        };

        // --- XỬ LÝ AVATAR ---
        if (isset($data['avatar']) && $data['avatar']) {
            // Nếu avatar hiện tại không phải file default thì xóa
            if ($group->avatar_url && !$isDefaultFile($group->avatar_url, 'default-avatar.webp')) {
                $relativePath = str_replace(asset('storage/'), '', $group->avatar_url);
                if ($relativePath) {
                    Storage::disk('public')->delete($relativePath);
                }
            }

            // Upload avatar mới
            $path = $data['avatar']->store('avatars', 'public');
            $data['avatar_url'] = asset('storage/' . $path);
            unset($data['avatar']);
        }

        // --- XỬ LÝ COVER ---
        if (isset($data['cover']) && $data['cover']) {
            // SỬA: kiểm tra cover_url (chứ không phải avatar_url) và filename default cover nếu có
            // Giả sử file default cover tên là 'default-avatar.webp' (nếu bạn có tên khác thì sửa)
            if ($group->cover_url && !$isDefaultFile($group->cover_url, 'default-avatar.webp')) {
                $relativePath = str_replace(asset('storage/'), '', $group->cover_url);
                if ($relativePath) {
                    Storage::disk('public')->delete($relativePath);
                }
            }

            // Upload cover mới
            $path = $data['cover']->store('covers', 'public');
            $data['cover_url'] = asset('storage/' . $path);
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
