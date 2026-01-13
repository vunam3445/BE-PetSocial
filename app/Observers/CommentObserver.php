<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\UserInteraction;
use Illuminate\Support\Str;

class CommentObserver
{
    public function created(Comment $comment)
    {
        $post = $comment->post;
        if ($post) {
            UserInteraction::create([
                'interaction_id' => Str::uuid(),
                'user_id' => $comment->user_id,
                'post_id' => $comment->post_id,
                'action_type' => 'comment',
                'weight' => 3, // Comment quan trọng hơn Like
                'metadata' => [
                    'tags' => $post->tags->pluck('name')->toArray(),
                    'content_preview' => Str::limit($comment->content, 50)
                ],
            ]);
        }
    }
}