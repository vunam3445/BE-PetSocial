<?php

namespace App\Repositories\ProfileRepository;

use App\Models\User;

class ProfileRepository implements ProfileInterface
{
    public function updateProfile(string $userId, array $data)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $user->update($data);
        return $user;
    }

    public function getProfile(string $userId): array
    {
        $user = User::withCount(['followers', 'followings'])->find($userId);

        if (!$user) {
            return [];
        }

        $pets = $user->pets()->get()->map(function ($pet) {
            return [
                'petId' => $pet->pet_id,
                'name' => $pet->name,
                'avatar_url' => $pet->avatar_url,

            ];
        })->toArray();

        return [
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'cover_url' => $user->cover_url,
                'bio' => $user->bio,
                'follower_count' => $user->followers_count,
                'following_count' => $user->followings_count,
            ],
            'pets' => $pets,
        ];
    }


    public function deleteProfile(string $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        return $user->delete();
    }

    public function getUser(string $userId)
    {
        $user = User::find($userId);
        return $user;
    }

    public function getMediaByUser(string $field, $value, array $types, int $perPage = 10)
{
    return \App\Models\PostMedia::whereHas('post.author', function ($query) use ($field, $value) {
            $query->where($field, $value);
        })
        ->whereIn('media_type', $types)
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage);
}


}
