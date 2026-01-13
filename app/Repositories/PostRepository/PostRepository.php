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
    $preferredPostIds = [];

    if ($userId) {
        // 1. Lấy Top 3 Tags dựa trên xu hướng mới nhất (updated_at) và điểm số (score)
        $topTags = DB::table('user_interest_profiles')
            ->where('user_id', $userId)
            ->where('score', '>', 0)
            ->orderBy('updated_at', 'desc') // Tag vừa tương tác (Thằn lằn) lên trước
            ->orderBy('score', 'desc')
            ->limit(3)
            ->get();

        if ($topTags->isNotEmpty()) {
            /** * 2. Phân bổ Quota (Hạn mức) cho 20 bài:
             * Tag 1 (Hot nhất): 8 bài (40%)
             * Tag 2: 4 bài (20%)
             * Tag 3: 2 bài (10%)
             * Còn lại 6 bài (30%) dành cho ngẫu nhiên khám phá.
             */
            $quotas = [0 => 8, 1 => 4, 2 => 2];

            foreach ($topTags as $index => $tag) {
                $tagQuota = $quotas[$index] ?? 0;

                $ids = DB::table('posts')
                    ->join('post_tags', 'posts.post_id', '=', 'post_tags.post_id')
                    ->join('tags', 'post_tags.tag_id', '=', 'tags.tag_id')
                    ->where('tags.name', $tag->tag_name)
                    ->where('posts.status', 'approved')
                    ->where('posts.visibility', '!=', 'private')
                    ->where('posts.author_id', '!=', $userId)
                    // Tránh lấy trùng bài viết nếu một bài có nhiều tag trong top 3
                    ->whereNotIn('posts.post_id', $preferredPostIds) 
                    ->orderBy('posts.created_at', 'desc')
                    ->limit($tagQuota)
                    ->pluck('posts.post_id')
                    ->toArray();

                $preferredPostIds = array_merge($preferredPostIds, $ids);
            }
        }
    }

    // 3. Lấy bài viết NGẪU NHIÊN để lấp đầy limit 20 bài
    // (Bao gồm 6 bài mặc định + bài bù đắp nếu các tag trên không đủ bài)
    $finalRandomLimit = $limit - count($preferredPostIds);
    
    $randomPostIds = DB::table('posts')
        ->where('status', 'approved')
        ->where('visibility', '!=', 'private')
        ->where('author_id', '!=', $userId)
        ->whereNotIn('post_id', $preferredPostIds)
        ->inRandomOrder()
        ->limit($finalRandomLimit)
        ->pluck('post_id')
        ->toArray();

    // 4. Gộp và TRỘN (Shuffle) để các bài cùng tag không nằm cạnh nhau
    $allPostIds = array_merge($preferredPostIds, $randomPostIds);
    if (empty($allPostIds)) {
        return $this->getQuery()->where('post_id', null)->paginate($limit);
    }
    
    shuffle($allPostIds); // Cực kỳ quan trọng để bảng tin đa dạng

    // 5. Query lấy dữ liệu chi tiết
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

    // 6. Sắp xếp theo thứ tự đã trộn (PostgreSQL)
    $idsString = implode(',', array_map(fn($id) => "'$id'", $allPostIds));
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
