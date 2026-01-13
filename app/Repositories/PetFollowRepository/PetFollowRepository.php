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
            // 1. Tìm bản ghi interaction follow_pet TRƯỚC khi xóa
            $interaction = UserInteraction::where('user_id', $userId)
                ->where('action_type', 'follow_pet')
                ->where('metadata->pet_id', $petId)
                ->first();

            if ($interaction) {
                // Lấy danh sách tags từ metadata (Xử lý trường hợp metadata là string hoặc array)
                $metadata = is_string($interaction->metadata)
                    ? json_decode($interaction->metadata, true)
                    : $interaction->metadata;

                $tags = $metadata['tags'] ?? [];

                if (!empty($tags)) {
                    foreach ($tags as $tagName) {
                        $tagName = strtolower(trim($tagName));

                        // 2. Giảm score đi 10 cho mỗi tag trong profile
                        DB::table('user_interest_profiles')
                            ->where('user_id', $userId)
                            ->where('tag_name', $tagName)
                            ->decrement('score', 10);
                    }
                }

                // 4. Xóa bản ghi interaction để Python không tính toán lại nó nữa
                $interaction->delete();
            }

            // 5. Xóa bản ghi trong bảng pet_follows
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
