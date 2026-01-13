<?php

namespace App\Repositories\ReportRepository;

use App\Models\Report;
use App\Repositories\Base\BaseRepository;
use Illuminate\Support\Facades\Auth;

class ReportRepository extends BaseRepository implements ReportInterface
{
    public function __construct(Report $model)
    {
        return parent::__construct($model);
    }

    public function getReportOfGroup(string $groupId, int $limit = 20)
    {
        $userId = Auth::id();

        return $this->model
            ->where('status','pending')
            ->with([
                // --- Post giống PostRepository ---
                'post.media',
                'post.author:user_id,name,avatar_url',
                'post.sharedPost.media',
                'post.sharedPost.author:user_id,name,avatar_url',
                'post.sharedPost.tags'
            ])
            ->whereHas('post', function ($q) use ($groupId) {
                $q->where('group_id', $groupId);
            })

            // --- counts bằng subquery ---
            ->addSelect([
                'reports_count' => \App\Models\Report::selectRaw('COUNT(*)')
                    ->from('reports as r2')
                    ->whereColumn('r2.post_id', 'reports.post_id'),
            ])
            ->addSelect([
                'comments_count' => \App\Models\Comment::selectRaw('COUNT(*)')
                    ->whereColumn('post_id', 'reports.post_id'),
            ])
            ->addSelect([
                'likes_count' => \App\Models\Like::selectRaw('COUNT(*)')
                    ->whereColumn('post_id', 'reports.post_id'),
            ])
            ->addSelect([
                'is_liked' => \App\Models\Like::selectRaw('COUNT(*)')
                    ->whereColumn('post_id', 'reports.post_id')
                    ->where('user_id', $userId),
            ])

            // --- is_owner giống PostRepository ---
            ->selectRaw('reports.*, (
                    SELECT (posts.author_id = ?)
                    FROM posts
                    WHERE posts.post_id = reports.post_id
                ) AS is_owner', [$userId])

            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
