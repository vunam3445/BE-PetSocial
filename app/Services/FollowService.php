<?php

namespace App\Services;

use App\Repositories\FollowRepository\FollowInterface;

class FollowService
{
    protected FollowInterface $followRepository;

    public function __construct(FollowInterface $followRepository)
    {
        $this->followRepository = $followRepository;
    }

    /**
     * Follow 1 user
     */
    public function follow(string $followerId, string $followedId)
    {
        if ($followerId === $followedId) {
            throw new \InvalidArgumentException("Bạn không thể tự follow chính mình.");
        }

        return $this->followRepository->follow($followerId, $followedId);
    }

    /**
     * Unfollow 1 user
     */
    public function unfollow(string $followerId, string $followedId): bool
    {
        return $this->followRepository->unfollow($followerId, $followedId);
    }

    /**
     * Danh sách user mà $userId đang follow
     */
    public function getFollowing(string $userId, int $limit = 20)
    {
        return $this->followRepository->getFollowing($userId, $limit);
    }

    /**
     * Danh sách user đang follow $userId
     */
    public function getFollowers(string $userId, int $limit = 20)
    {
        return $this->followRepository->getFollowers($userId, $limit);
    }

    /**
     * Kiểm tra quan hệ follow
     */
    public function isFollowing(string $followerId, string $followedId): bool
    {
        return $this->followRepository->isFollowing($followerId, $followedId);
    }
}
