<?php

namespace App\Repositories\SearchRepository;

use App\Models\User;
use App\Models\Post;
use App\Repositories\SearchRepository\SearchInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SearchRepository implements SearchInterface
{
public function searchUsers(string $keyword, int $perPage = 10)
{
    $authId = Auth::id();

    return User::withCount('followers')
        ->withExists(['followers as is_followed' => function ($query) use ($authId) {
            $query->where('follower_id', $authId);
        }])
        ->where('user_id', '!=', $authId) // loại bỏ chính mình
        ->where(function ($query) use ($keyword) {
            $query->where('name', 'ILIKE', "%{$keyword}%")
                  ->orWhere('email', 'ILIKE', "%{$keyword}%");
        })
        ->paginate($perPage, [
            'user_id',
            'name',
            'avatar_url',
            'bio',
        ]);
}



    public function searchPosts(string $keyword, int $perPage = 10)
    {
        $query = Post::where(function ($q) use ($keyword) {
            // Tìm trong caption
            $q->where('caption', 'ILIKE', "%{$keyword}%")
                // Tìm trong tags
                ->orWhereHas('tags', function ($tagQuery) use ($keyword) {
                    $tagQuery->where('name', 'ILIKE', "%{$keyword}%");
                });
        })
            ->with([
                'author:user_id,name,avatar_url',
                'sharedPost.media',
                'sharedPost.author:user_id,name,avatar_url',
                'sharedPost.tags',
                'tags',
                'media',
            ]);

        // Đếm like và comment
        $query->withCount(['comments', 'likes']);

        // Nếu có user đăng nhập thì thêm cột is_liked
        $userId = Auth::id();
        if ($userId) {
            $query->withCount([
                'likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ]);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }


public function searchPets(string $keyword, int $perPage = 10)
{
    $authId = Auth::id();

    return \App\Models\Pet::where(function ($query) use ($keyword) {
            $query->where('name', 'ILIKE', "%{$keyword}%")
                  ->orWhere('breed', 'ILIKE', "%{$keyword}%")
                  ->orWhere('type', 'ILIKE', "%{$keyword}%");
        })
        ->where('user_id', '!=', $authId)
        ->select([
            'pet_id',
            'name',
            'avatar_url',
            'breed',
            'type',
            'user_id as owner_id',
            DB::raw("EXISTS (
                SELECT 1 
                FROM follows 
                WHERE follows.followed_id = pets.user_id
                AND follows.follower_id = '{$authId}'
            ) as is_following")
        ])
        ->paginate($perPage);
}

}
