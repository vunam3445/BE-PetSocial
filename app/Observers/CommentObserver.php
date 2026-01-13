<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\UserInteraction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CommentObserver
{
    public function created(Comment $comment)
    {
        $post = $comment->post;
        if ($post) {
            $tags = $post->tags->pluck('name')->toArray();
            $weight = 3;
            UserInteraction::create([
                'interaction_id' => Str::uuid(),
                'user_id' => $comment->user_id,
                'post_id' => $comment->post_id,
                'action_type' => 'comment',
                'weight' => $weight,
                'metadata' => [
                    'tags' => $tags,
                    'content_preview' => Str::limit($comment->content, 50)
                ],
            ]);

        }
    }

    public function deleted(Comment $comment)
    {
        // 1. Tìm bản ghi interaction tương ứng của comment này
        // Lưu ý: Nếu user comment nhiều lần vào 1 bài, ta chỉ xóa 1 bản ghi interaction gần nhất
        $interaction = UserInteraction::where('user_id', $comment->user_id)
            ->where('post_id', $comment->post_id)
            ->where('action_type', 'comment')
            ->latest() 
            ->first();

        if ($interaction) {
            $metadata = is_string($interaction->metadata) 
                ? json_decode($interaction->metadata, true) 
                : $interaction->metadata;

            $tags = $metadata['tags'] ?? [];
            $weight = $interaction->weight ?? 3;

            // 2. Trừ điểm sở thích (Đảm bảo sàn bằng 0)
            foreach ($tags as $tagName) {
                DB::table('user_interest_profiles')
                    ->where('user_id', $comment->user_id)
                    ->where('tag_name', strtolower(trim($tagName)))
                    ->update([
                        'score' => DB::raw("GREATEST(0, score - $weight)"),
                        'updated_at' => now()
                    ]);
            }

            // 3. Xóa bản ghi interaction
            $interaction->delete();
        }
    }
}