<?php

namespace App\Services;

use App\Repositories\FollowRepository\FollowInterface;
use App\Repositories\PetFollowRepository\PetFollowInterface;

class FollowService
{
    protected FollowInterface $followRepository;
    protected PetFollowInterface $petFollowRepository;

    public function __construct(FollowInterface $followRepository, PetFollowInterface $petFollowRepository)
    {
        $this->followRepository = $followRepository;
        $this->petFollowRepository = $petFollowRepository;
    }

    /**
     * Follow 1 user
     */
  public function follow(string $followerId, string $targetId, string $type)
    {
        return match($type) {
            'user' => $this->followRepository->follow($followerId, $targetId),
            'pet'  => $this->petFollowRepository->follow($followerId, $targetId),
            default => throw new \InvalidArgumentException("Invalid follow type")
        };
    }

    /**
     * Unfollow 1 user
     */
    public function unfollow(string $followerId, string $targetId, string $type)
    {
        return match($type) {
            'user' => $this->followRepository->unfollow($followerId, $targetId),
            'pet'  => $this->petFollowRepository->unfollow($followerId, $targetId),
            default => throw new \InvalidArgumentException("Invalid follow type")
        };
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
    public function isFollowing(string $followerId, string $targetId, string $type): bool
    {
        return match($type) {
            'user' => $this->followRepository->isFollowing($followerId, $targetId),
            'pet'  => $this->petFollowRepository->isFollowing($followerId, $targetId),
            default => throw new \InvalidArgumentException("Invalid follow type")
        };
    }
}
