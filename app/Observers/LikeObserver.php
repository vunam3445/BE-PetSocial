<?php

namespace App\Observers;

use App\Models\Like;
use App\Models\UserInteraction;
use Illuminate\Support\Str;

class LikeObserver
{
    public function created(Like $like)
    {
        // Lưu ý: Đảm bảo quan hệ post() tồn tại trong Model Like
        $post = \App\Models\Post::find($like->post_id);
        if ($post) {
            UserInteraction::create([
                'interaction_id' => Str::uuid(),
                'user_id' => $like->user_id,
                'post_id' => $like->post_id,
                'action_type' => 'like',
                'weight' => 1,
                'metadata' => [
                    'tags' => $post->tags->pluck('name')->toArray()
                ],
            ]);
        }
    }

    public function deleted(Like $like)
    {
        // Xóa interaction khi unlike để AI cập nhật lại sở thích
        UserInteraction::where('user_id', $like->user_id)
            ->where('post_id', $like->post_id)
            ->where('action_type', 'like')
            ->delete();
    }
}