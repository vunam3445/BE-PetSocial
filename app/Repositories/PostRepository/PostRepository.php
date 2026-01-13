<?php

namespace App\Repositories\PostRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function getAllPosts(array $relations = [], array $withCount = [], int $limit = 20) {
    $userId = Auth::id();
    $targetPreferred = (int) round($limit * 0.7); 

    $preferredPostIds = [];
    if ($userId) {
        $topTags = DB::table('user_interest_profiles')
            ->where('user_id', $userId)
            ->where('score', '>', 0)
            ->orderBy('score', 'desc')
            ->limit(3)
            ->pluck('tag_name')
            ->toArray();

        if (!empty($topTags)) {
            $preferredPostIds = DB::table('posts')
                ->join('post_tags', 'posts.post_id', '=', 'post_tags.post_id')
                ->join('tags', 'post_tags.tag_id', '=', 'tags.tag_id')
                ->whereIn('tags.name', $topTags)
                ->where('posts.status', 'approved')
                ->where('posts.visibility', '!=', 'private')
                ->where('posts.author_id', '!=', $userId)
                ->orderBy('posts.created_at', 'desc')
                ->limit($targetPreferred)
                ->pluck('posts.post_id')
                ->toArray();
        }
    }

    $finalRandomLimit = $limit - count($preferredPostIds);
    $randomPostIds = DB::table('posts')
        ->where('status', 'approved')
        ->where('visibility', '!=', 'private')
        ->whereNotIn('post_id', $preferredPostIds)
        ->inRandomOrder()
        ->limit($finalRandomLimit)
        ->pluck('post_id')
        ->toArray();

    $allPostIds = array_merge($preferredPostIds, $randomPostIds);

    // Nếu không có bài viết nào thì trả về phân trang trống
    if (empty($allPostIds)) {
        return $this->getQuery()->where('id', null)->paginate($limit);
    }

    $query = $this->getQuery()
        ->whereIn('post_id', $allPostIds)
        ->with(array_merge($relations, [
            'sharedPost.media',
            'sharedPost.author:user_id,name,avatar_url',
            'sharedPost.tags'
        ]));

    if ($userId) {
        $query->withCount(array_merge($withCount, [
            'comments',
            'likes as is_liked' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }
        ]));
    }

    // FIX LỖI Ở ĐÂY: Sử dụng position để giữ đúng thứ tự 70/30 trong PostgreSQL
    $idsString = implode(',', $allPostIds);
    $query->orderByRaw("position(post_id::text in ?)", [$idsString]);

    return $query->selectRaw('*, (author_id = ?) as is_owner', [$userId])
                 ->paginate($limit);
}
    public function getPostsOfGroup(
        string $groupId,
        array $relations = [],
        array $withCount = [],
        int $limit = 20
    ) {
        $userId = Auth::id();

        $query = $this->getQuery()
            ->where('group_id', $groupId)
            ->where('status', 'approved')
            ->with(array_merge($relations, [
                'media',
                'author:user_id,name,avatar_url',
                'sharedPost.media',
                'sharedPost.author:user_id,name,avatar_url',
                'sharedPost.tags'
            ]));

        // Optional withCount từ FE
        if (!empty($withCount)) {
            $query->withCount($withCount);
        }

        // luôn trả về comments_count + is_liked
        if ($userId) {
            $query->withCount([
                'comments',
                'likes',
                'likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ]);
        }

        // đánh dấu chủ sở hữu bài viết
        $query->selectRaw('*, (author_id = ?) as is_owner', [$userId]);

        return $query
            ->orderBy('updated_at', 'desc')
            ->paginate($limit);
    }
    public function getPostsPendingOfGroup(
        string $groupId,
        array $relations = [],
        array $withCount = [],
        int $limit = 20
    ) {
        $userId = Auth::id();

        $query = $this->getQuery()
            ->where('group_id', $groupId)
            ->where('status', 'pending')
            ->with(array_merge($relations, [
                'media',
                'author:user_id,name,avatar_url',
                'sharedPost.media',
                'sharedPost.author:user_id,name,avatar_url',
                'sharedPost.tags'
            ]));

        // Optional withCount từ FE
        if (!empty($withCount)) {
            $query->withCount($withCount);
        }

        // luôn trả về comments_count + is_liked
        if ($userId) {
            $query->withCount([
                'comments',
                'likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ]);
        }

        // đánh dấu chủ sở hữu bài viết
        $query->selectRaw('*, (author_id = ?) as is_owner', [$userId]);

        return $query
            ->orderBy('updated_at', 'desc')
            ->paginate($limit);
    }
}
