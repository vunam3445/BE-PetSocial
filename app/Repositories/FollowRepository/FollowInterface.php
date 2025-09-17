<?php

namespace App\Repositories\FollowRepository;

use App\Repositories\Base\BaseInterface;

interface FollowInterface extends BaseInterface
{
    /**
     * Follow 1 user
     */
    public function follow(string $followerId, string $followedId);

    /**
     * Unfollow 1 user
     */
    public function unfollow(string $followerId, string $followedId);

    /**
     * Lấy danh sách người mà user đang follow
     */
    public function getFollowing(string $userId, int $limit = 20);

    /**
     * Lấy danh sách người đang follow user
     */
    public function getFollowers(string $userId, int $limit = 20);

    /**
     * Kiểm tra xem user này có follow user kia không
     */
    public function isFollowing(string $followerId, string $followedId): bool;
}
