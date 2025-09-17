<?php

namespace App\Repositories\FollowRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Follow;

class FollowRepository extends BaseRepository implements FollowInterface
{
    public function __construct(Follow $model)
    {
        parent::__construct($model);
    }

    /**
     * Follow 1 user
     */
    public function follow(string $followerId, string $followedId)
    {
        // tránh self-follow
        if ($followerId === $followedId) {
            return null;
        }

        return $this->model->firstOrCreate([
            'follower_id' => $followerId,
            'followed_id' => $followedId,
        ]);
    }

    /**
     * Unfollow 1 user
     */
    public function unfollow(string $followerId, string $followedId): bool
    {
        return $this->model
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->delete() > 0;
    }

    /**
     * Lấy danh sách user mà $userId đang follow
     */
    public function getFollowing(string $userId, int $limit = 20)
    {
        return $this->model
            ->with('followed') // quan hệ trong model Follow
            ->where('follower_id', $userId)
            ->paginate($limit);
    }

    /**
     * Lấy danh sách user đang follow $userId
     */
    public function getFollowers(string $userId, int $limit = 20)
    {
        return $this->model
            ->with('follower') // quan hệ trong model Follow
            ->where('followed_id', $userId)
            ->paginate($limit);
    }

    /**
     * Kiểm tra xem user này có follow user kia không
     */
    public function isFollowing(string $followerId, string $followedId): bool
    {
        return $this->model
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->exists();
    }
}
