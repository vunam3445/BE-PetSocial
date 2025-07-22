<?php
namespace App\Services;
use App\Repositories\ProfileRepository\ProfileInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileService
{
    public function __construct(private ProfileInterface $profileRepository)
    {
    }

    public function updateProfile(string $userId, array $data): bool
    {
        return $this->profileRepository->updateProfile($userId, $data);
    }

public function getProfile(?string $userId): array
{
    if ($userId) {
        return $this->profileRepository->getProfile($userId);
    }

    $user = Auth::user();
    return [
        'userId' => $user->user_id,
        'name' => $user->name,
        'avatar_url' => $user->avatar_url,
        'cover_url' => $user->cover_url,
        'bio' => $user->bio,
        'date_of_birth' => $user->date_of_birth,
        'gender' => $user->gender,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
    ];
}


    public function deleteProfile(string $userId): bool
    {
        return $this->profileRepository->deleteProfile($userId);
    }
}