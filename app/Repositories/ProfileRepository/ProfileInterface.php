<?php
namespace App\Repositories\ProfileRepository;

interface ProfileInterface

{
    public function updateProfile(string $userId, array $data): bool;
    public function getProfile(?string $userId): array;
    public function deleteProfile(string $userId): bool;
}
