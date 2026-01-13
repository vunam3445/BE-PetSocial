<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewPostNotification;
use App\Notifications\NewPostOfPetNotification;
use Illuminate\Support\Facades\Auth;
use App\Notifications\PostDeletedNotification;

class PostObserver
{
    public function created(Post $post)
    {
        // 1. Chỉ thông báo nếu bài viết đã được phê duyệt
        if ($post->status !== 'approved') {
            return;
        }

        // 2. Kiểm tra nếu bài viết thuộc về một thú cưng
        if ($post->pet_id) {
            $pet = $post->pet; // Đảm bảo đã định nghĩa relation pet() trong Model Post
            if ($pet) {
                // Lấy những người theo dõi thú cưng
                $pet->followers()->chunk(200, function ($followers) use ($post) {
                    Notification::send($followers, new NewPostOfPetNotification($post));
                });
            }
        } else {
            // 3. Nếu không có pet_id, gửi cho người theo dõi tác giả (cũ)
            $author = $post->author;
            if ($author) {
                $author->followers()->chunk(200, function ($followers) use ($post) {
                    Notification::send($followers, new NewPostNotification($post));
                });
            }
        }
        if ($post->shared_post_id) {
        $originalPost = $post->sharedPost;
        if ($originalPost) {
            \App\Models\UserInteraction::create([
                'interaction_id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $post->author_id,
                'post_id' => $post->shared_post_id, // Lưu ID bài gốc
                'action_type' => 'share',
                'weight' => 5,
                'metadata' => [
                    'tags' => $originalPost->tags->pluck('name')->toArray(),
                    'caption' => $post->caption // Caption của người share
                ],
            ]);
        }
    }
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleting(Post $post)
    {
        $currentUserId = Auth::id();

        // CHỈ gửi thông báo nếu người xóa KHÔNG PHẢI là tác giả (tức là Admin/Mod xóa)
        if ($currentUserId && (string)$post->author_id !== (string)$currentUserId) {
            $author = $post->author;
            $group = $post->group; // Nếu bài viết thuộc group

            if ($author) {
                // Gửi thông báo vi phạm cho tác giả
                $author->notify(new PostDeletedNotification($group));
            }
        }
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        //
    }
}
