<?php

namespace App\Observers;

use App\Models\PetFollow;
use App\Models\UserInteraction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PetFollowObserver
{
    public function created(PetFollow $follow)
    {
        $pet = \App\Models\Pet::find($follow->pet_id);
        if ($pet) {
            UserInteraction::create([
                'interaction_id' => Str::uuid(),
                'user_id' => $follow->user_id,
                'post_id' => null, // Quan trọng: null cho hành động follow
                'action_type' => 'follow_pet',
                'weight' => 10, // Ưu tiên cao nhất
                'metadata' => [
                    'pet_id' => $pet->pet_id,
                    'pet_name' => $pet->name,
                    'tags' => $pet->tags, // Giả sử Pet cũng có tags như "Husky", "Cute"
                    'type' => $pet->type,
                    'breed' => $pet->breed
                ],
            ]);
        }
    }
    public function deleted(PetFollow $follow): void
    {

        UserInteraction::where('user_id', $follow->user_id)
            ->where('action_type', 'follow_pet')
            // Sử dụng whereJsonContains để an toàn hơn với PostgreSQL
            ->whereJsonContains('metadata', ['pet_id' => $follow->pet_id])
            ->delete();
    }
}
