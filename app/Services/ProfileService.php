<?php
namespace App\Services;
use App\Repositories\ProfileRepository\ProfileInterface;
class ProfileService
{
    public function __construct(private ProfileInterface $profileRepository)
    {
    }

    public function updateProfile(string $userId, array $data): bool
    {
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
}