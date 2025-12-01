<?php

namespace App\Repositories\PetFollowRepository;

use App\Models\PetFollow;
use App\Repositories\Base\BaseRepository;

class PetFollowRepository extends BaseRepository implements PetFollowInterface
{

    public function __construct(PetFollow $model)
    {
        parent::__construct($model);
    }
    public function follow(string $userId, string $petId)
    {
        // tránh self-follow
        if ($userId === $petId) {
            return null;
        }
        return $this->model->firstOrCreate([
            'user_id' => $userId,
            'pet_id' => $petId,
        ]);
    }

    public function unfollow(string $userId, string $petId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('pet_id', $petId)
            ->delete() > 0;
    }

    public function isFollowing(string $userId, string $petId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('pet_id', $petId)
            ->exists();
    }
}
