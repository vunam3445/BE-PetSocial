<?php

namespace App\Repositories\PetFollowRepository;

use App\Models\PetFollow;
use App\Repositories\Base\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Models\UserInteraction;
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
        return DB::transaction(function () use ($userId, $petId) {
            // 1. Xóa interaction của AI trước
            // Điều này thay thế cho logic trong Observer
            UserInteraction::where('user_id', $userId)
                ->where('action_type', 'follow_pet')
                ->where('metadata->pet_id', $petId)
                ->delete();

            // 2. Xóa bản ghi follow bằng Query Builder
            // Dùng DB::table để tránh lỗi Model "zero-length delimited identifier"
            $deleted = DB::table('pet_follows')
                ->where('user_id', $userId)
                ->where('pet_id', $petId)
                ->delete();

            return $deleted > 0;
        });
    }

    public function isFollowing(string $userId, string $petId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('pet_id', $petId)
            ->exists();
    }
}
