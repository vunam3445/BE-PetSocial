<?php

namespace App\Observers;

use App\Models\Pet;
use App\Models\UserInteraction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PetObserver
{
    /**
     * Xử lý khi người dùng thêm thú cưng mới.
     * Đây là tín hiệu sở thích mạnh nhất (Weight: 20).
     */
    public function created(Pet $pet): void
    {
        UserInteraction::create([
            'interaction_id' => (string) Str::uuid(),
            'user_id'        => $pet->user_id,
            'post_id'        => null, // Không gắn với bài viết cụ thể
            'action_type'    => 'own_pet',
            'weight'         => 20, 
            'metadata'       => [
                'pet_id'  => $pet->pet_id,
                'species' => $pet->type,  // Ví dụ: Dog, Cat
                'breed'   => $pet->breed, // Ví dụ: Golden, Poodle
                // AI sẽ cào các tag này để phân phối bài viết cùng chủ đề
                'tags'    => array_filter([$pet->type, $pet->breed]), 
            ],
        ]);
    }

    /**
     * Xử lý khi cập nhật thông tin thú cưng.
     */
    public function updated(Pet $pet): void
    {
        // Nếu người dùng đổi giống loài hoặc loại pet, cập nhật lại metadata trong interactions
        $interaction = UserInteraction::where('user_id', $pet->user_id)
            ->where('action_type', 'own_pet')
            ->where('metadata->pet_id', $pet->pet_id)
            ->first();

        if ($interaction) {
            $interaction->update([
                'metadata' => [
                    'pet_id'  => $pet->pet_id,
                    'species' => $pet->type,
                    'breed'   => $pet->breed,
                    'tags'    => array_filter([$pet->type, $pet->breed]),
                ]
            ]);
        }
    }

    /**
     * Xử lý khi người dùng xóa thú cưng.
     */
    public function deleted(Pet $pet): void
    {
        
        UserInteraction::where('user_id', $pet->user_id)
            ->where('action_type', 'own_pet')
            ->where('metadata->pet_id', $pet->pet_id)
            ->delete();
    }

    /**
     * Xử lý khi khôi phục thú cưng (nếu dùng SoftDeletes).
     */
    public function restored(Pet $pet): void
    {
        $this->created($pet);
    }

    /**
     * Xử lý khi xóa vĩnh viễn khỏi DB.
     */
    public function forceDeleted(Pet $pet): void
    {
        $this->deleted($pet);
    }
}