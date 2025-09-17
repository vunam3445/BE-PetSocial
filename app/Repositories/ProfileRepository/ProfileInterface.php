<?php
namespace App\Repositories\ProfileRepository;

interface ProfileInterface

{
    public function updateProfile(string $userId, array $data);
    public function getProfile(string $userId): array;
    public function deleteProfile(string $userId): bool;
    public function getUser(string $userId);
    public function getMediaByUser(string $field, $value, array $dk, int $limit = 10);
    public function getFollowers (string $userId, int $limit);
    public function getFollowing(string $userId, int $limit);
}
