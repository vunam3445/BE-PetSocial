<?php

namespace App\Services;

use App\Repositories\ProfileRepository\ProfileInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\UploadedFile;

class ProfileService
{
    public function __construct(private ProfileInterface $profileRepository) {}

    public function updateProfile(string $userId, array $data)
    {
        $userAuth = Auth::id();
        if (!$userAuth) {
            throw new HttpException(401, 'Chưa xác thực người dùng.');
        }

        // ✅ Kiểm tra người dùng có đúng là chủ tài khoản hay không
        if ($userAuth!== $userId) {
            throw new HttpException(403, 'Bạn không có quyền cập nhật profile này.');
        }

        // ✅ Xử lý ảnh avatar nếu có và đúng kiểu file
        if (isset($data['avatar_url']) && $data['avatar_url'] instanceof UploadedFile) {
            $avatarPath = $data['avatar_url']->store('avatars', 'public');
            $data['avatar_url'] = asset('storage/' . $avatarPath);
        }

        // ✅ Xử lý ảnh cover nếu có và đúng kiểu file
        if (isset($data['cover_url']) && $data['cover_url'] instanceof UploadedFile) {
            $coverPath = $data['cover_url']->store('covers', 'public');
            $data['cover_url'] = asset('storage/' . $coverPath);
        }

        return $this->profileRepository->updateProfile($userId, $data);
    }

    public function getProfile(string $userId): array
    {
        return $this->profileRepository->getProfile($userId);
    }



    public function deleteProfile(string $userId): bool
    {
        return $this->profileRepository->deleteProfile($userId);
    }

    public function getMedia(string $userId, string $mediaType)
    {
        return $this->profileRepository->getMediaByUser('user_id', $userId, [$mediaType], 10);
    }
}
