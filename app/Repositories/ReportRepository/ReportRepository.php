<?php

namespace App\Repositories\ReportRepository;

use App\Models\Report;
use App\Repositories\Base\BaseRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;

class ReportRepository extends BaseRepository implements ReportInterface
{
    public function __construct(Report $model)
    {
        return parent::__construct($model);
    }
    public function getReportOfGroup(string $groupId, int $limit = 20)
{
    $userId = Auth::id();
    $postClass = \App\Models\Post::class;

    return Post::query()
        ->where('group_id', $groupId)
        ->whereHas('reports', function ($query) {
            $query->where('status', 'pending');
        })
        ->with([
            'media',
            'author:user_id,name,avatar_url',
            'sharedPost.media',
            'sharedPost.author:user_id,name,avatar_url',
            'sharedPost.tags',
            // Chỉ lấy các trường cần thiết từ bảng reports
            // Lưu ý: Luôn phải giữ reportable_id và reportable_type để Eloquent khớp nối đa hình
            'reports' => function ($query) {
                $query->select('report_id', 'reportable_id', 'reportable_type', 'reason', 'description', 'created_at')
                      ->where('status', 'pending')
                      ->orderBy('created_at', 'desc');
            }
        ])
        ->addSelect([
            'reports_count' => Report::selectRaw('count(*)')
                ->whereColumn('reportable_id', 'posts.post_id')
                ->where('reportable_type', $postClass)
                ->where('status', 'pending'),

            'comments_count' => \App\Models\Comment::selectRaw('count(*)')
                ->whereColumn('post_id', 'posts.post_id'),

            'likes_count' => \App\Models\Like::selectRaw('count(*)')
                ->whereColumn('post_id', 'posts.post_id'),

            'is_liked' => \App\Models\Like::selectRaw('count(*)')
                ->whereColumn('post_id', 'posts.post_id')
                ->where('user_id', $userId),
        ])
        ->selectRaw('posts.*, (author_id = ?) as is_owner', [$userId])
        ->orderByDesc(
            Report::select('created_at')
                ->whereColumn('reportable_id', 'posts.post_id')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(1)
        )
        ->paginate($limit);
}
}
