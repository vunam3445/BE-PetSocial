<?php

namespace App\Services;

use App\Repositories\LikeRepository\LikeInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Like;

class LikeService
{
    protected $likeRepository;

    public function __construct(LikeInterface $likeRepository)
    {
        $this->likeRepository = $likeRepository;
    }



    public function getLikesForPost(string $postId)
    {
        return $this->likeRepository->getLikesForPost($postId);
    }
    public function toggleLike( string $postId): bool
    {
        $userId = Auth::id();
        $like = Like::where('user_id', $userId)->where('post_id', $postId)->first();

        if ($like) {
            $like->delete();
            return false; // user đã unlike
        }

        Like::create([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);

        return true; // user vừa like
    }

    public function countLikes(string $postId): int
    {
        return Like::where('post_id', $postId)->count();
    }
}
