<?php

namespace App\Repositories\PostRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostRepository extends BaseRepository implements PostInterface
{
    public function __construct(Post $model)
    {
        parent::__construct($model);
    }

    public function getQuery()
    {
        return $this->model->newQuery();
    }

    public function getAllPosts(
        array $relations = [],
        array $withCount = [],
        int $limit = 20
    ) {
        $query = $this->getQuery()
            ->with(array_merge($relations, [
                'sharedPost.media',
                'sharedPost.author:user_id,name,avatar_url',
                'sharedPost.tags'
            ]));

        if (!empty($withCount)) {
            $query->withCount($withCount);
        }

        $userId = Auth::id();
        if ($userId) {
            $query->withCount([
                'comments',
                'likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ]);
        }

        return $query
            ->where('visibility', '!=', 'private') // ✅ loại private
            ->orderBy('updated_at', 'desc')
            ->paginate($limit);
    }
}
