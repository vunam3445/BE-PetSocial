<?php
namespace App\Repositories\PetFollowRepository;

use App\Repositories\Base\BaseInterface;

interface PetFollowInterface extends BaseInterface
{
    public function follow(string $followerId, string $followedId);
    public function unfollow(string $followerId, string $followedId);
    public function isFollowing(string $followerId, string $followedId): bool;
}

