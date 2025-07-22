<?php
namespace App\Repositories\ProfileRepository;
use App\Models\User;
class ProfileRepository implements ProfileInterface
{
    public function updateProfile(string $userId, array $data): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $user->update($data);
        return true;
    }

    public function getProfile(string $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        return $user->toArray();
    }

    public function deleteProfile(string $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        return $user->delete();
    }
}