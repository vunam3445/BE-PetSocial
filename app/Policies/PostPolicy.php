<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Quyền cập nhật bài viết
     */
    public function update(User $user, Post $post): bool
    {
        return $user->user_id === $post->author_id;
    }

}