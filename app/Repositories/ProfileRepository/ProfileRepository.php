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

    // lấy 4 pet mới nhất
    $pets = $user->pets()
        ->orderBy('created_at', 'desc')
        ->take(4)
        ->get()
        ->map(function ($pet) {
            return [
                'petId' => $pet->pet_id,
                'name' => $pet->name,
                'avatar_url' => $pet->avatar_url,
            ];
        })
        ->toArray();

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

    public function getFollowers(string $userId, int $perPage = 10)
{
    $user = User::find($userId);

    if (!$user) {
        return [];
    }

    return $user->followers()
        ->select('users.user_id', 'users.name', 'users.avatar_url')
        ->withCount(['followers', 'followings'])
        ->orderBy('users.name', 'asc')
        ->paginate($perPage)
        ->through(function ($follower) {
            return [
                'user_id' => $follower->user_id,
                'name' => $follower->name,
                'avatar_url' => $follower->avatar_url,
                'follower_count' => $follower->followers_count,
                'following_count' => $follower->followings_count,
            ];
        });
}

public function getFollowing(string $userId, int $perPage = 10)
{
    $user = User::find($userId);

    if (!$user) {
        return [];
    }

    return $user->followings()
        ->select('users.user_id', 'users.name', 'users.avatar_url')
        ->withCount(['followers', 'followings'])
        ->orderBy('users.name', 'asc')
        ->paginate($perPage)
        ->through(function ($following) {
            return [
                'user_id' => $following->user_id,
                'name' => $following->name,
                'avatar_url' => $following->avatar_url,
                'follower_count' => $following->followers_count,
                'following_count' => $following->followings_count,
            ];
        });
}

}
