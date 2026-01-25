<?php

namespace App\Repositories\PostRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

   public function getAllPosts(array $relations = [], array $withCount = [], int $limit = 20)
{
    $userId = Auth::id();
    $query = $this->getQuery(); // Đảm bảo query xuất phát từ Model Post

    // 1. Cấu hình cơ bản (Status, Visibility, Recency)
    $query->where('posts.status', 'approved')
          ->where('posts.visibility', '!=', 'private')
          ->where('posts.created_at', '>=', now()->subDays(7));

    // 2. Quyền xem Group
    $query->where(function ($q) use ($userId) {
        $q->whereNull('posts.group_id')
          ->orWhereExists(function ($subQuery) use ($userId) {
              $subQuery->select(DB::raw(1))
                  ->from('groups')
                  ->whereColumn('groups.group_id', 'posts.group_id')
                  ->where(function ($g) use ($userId) {
                      $g->where('groups.visibility', '!=', 'private');
                      if ($userId) {
                          $g->orWhereExists(function ($m) use ($userId) {
                              $m->select(DB::raw(1))
                                ->from('group_members')
                                ->whereColumn('group_members.group_id', 'groups.group_id')
                                ->where('group_members.user_id', $userId);
                          });
                      }
                  });
          });
    });

    if ($userId) {
        // Seed cho Random
        $seedKey = 'feed_seed_' . $userId;
        $seed = Cache::remember($seedKey, now()->addMinutes(30), fn() => rand(-1000, 1000) / 1000);
        DB::statement("SELECT setseed($seed)");

        // Lấy Tags
        $topTagNames = DB::table('user_interest_profiles')
            ->where('user_id', $userId)
            ->where('score', '>', 0)
            ->orderByDesc('score')
            ->limit(3)
            ->pluck('tag_name')
            ->toArray();
        $tagInSql = !empty($topTagNames) ? "'" . implode("','", array_map(fn($t) => addslashes($t), $topTagNames)) . "'" : "''";

        // 3. Tính điểm và Rank ngay trong SELECT của Eloquent
        // Sử dụng các cột bí danh (alias) ở đây
        $query->select('posts.*')
            ->selectRaw("
                (
                    (CASE WHEN posts.pet_id IS NOT NULL AND EXISTS (SELECT 1 FROM pet_follows pf WHERE pf.user_id = ? AND pf.pet_id = posts.pet_id) THEN 120 ELSE 0 END) +
                    (CASE WHEN EXISTS (SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.followed_id = posts.author_id) THEN 100 ELSE 0 END) +
                    (CASE WHEN posts.group_id IS NOT NULL AND EXISTS (SELECT 1 FROM group_members gm WHERE gm.user_id = ? AND gm.group_id = posts.group_id) THEN 80 ELSE 0 END) +
                    (CASE WHEN EXISTS (SELECT 1 FROM post_tags pt JOIN tags t ON pt.tag_id = t.tag_id WHERE pt.post_id = posts.post_id AND t.name IN ($tagInSql)) THEN 50 ELSE 0 END) + 10
                ) as base_score
            ", [$userId, $userId, $userId])
            ->selectRaw("
                ROW_NUMBER() OVER(
                    PARTITION BY COALESCE(posts.group_id, '00000000-0000-0000-0000-000000000000'::uuid), posts.author_id 
                    ORDER BY posts.created_at DESC
                ) as rank_in_source
            ")
            ->selectRaw("(posts.author_id = ?) as is_owner", [$userId]);

        // 4. Sắp xếp: Vì không dùng Subquery được với withCount, ta phải copy lại biểu thức tính toán vào OrderBy
        // Đây là cách duy nhất để tránh lỗi 'column base_score does not exist' mà không làm hỏng withCount
        $scoreExpression = "
            (
                (CASE WHEN posts.pet_id IS NOT NULL AND EXISTS (SELECT 1 FROM pet_follows pf WHERE pf.user_id = '$userId' AND pf.pet_id = posts.pet_id) THEN 120 ELSE 0 END) +
                (CASE WHEN EXISTS (SELECT 1 FROM follows f WHERE f.follower_id = '$userId' AND f.followed_id = posts.author_id) THEN 100 ELSE 0 END) +
                (CASE WHEN posts.group_id IS NOT NULL AND EXISTS (SELECT 1 FROM group_members gm WHERE gm.user_id = '$userId' AND gm.group_id = posts.group_id) THEN 80 ELSE 0 END) +
                (CASE WHEN EXISTS (SELECT 1 FROM post_tags pt JOIN tags t ON pt.tag_id = t.tag_id WHERE pt.post_id = posts.post_id AND t.name IN ($tagInSql)) THEN 50 ELSE 0 END) + 10
            )
        ";
        
        $rankExpression = "ROW_NUMBER() OVER(PARTITION BY COALESCE(posts.group_id, '00000000-0000-0000-0000-000000000000'::uuid), posts.author_id ORDER BY posts.created_at DESC)";

        $query->orderByRaw("-log(1 - RANDOM()) / (GREATEST($scoreExpression - ($rankExpression - 1) * 20, 1)) ASC");

    } else {
        $query->select('posts.*')->selectRaw("0 as base_score, 1 as rank_in_source, false as is_owner");
        $query->orderByDesc('posts.created_at');
    }

    // 5. Eager Loading & Counts (Sẽ hoạt động vì Query xuất phát từ Model Post chuẩn)
    $query->with(array_merge($relations, [
        'group:group_id,name',
        'sharedPost.media',
        'sharedPost.author:user_id,name,avatar_url',
        'tags'
    ]));

    if ($userId) {
        $query->withCount(array_merge($withCount, [
            'comments',
            'likes as is_liked' => fn($q) => $q->where('user_id', $userId)
        ]));
    }

    return $query->paginate($limit);
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
            ->paginate(10);
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
                'sharedPost.tags',
                'pet:pet_id,name,avatar_url'
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
    public function resetSeed()
    {
        $userId = Auth::id();
        Cache::forget('feed_seed_' . ($userId ?? 'guest'));
    }
}
