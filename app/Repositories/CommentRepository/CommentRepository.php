<?php

namespace App\Repositories\CommentRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Comment;
use Illuminate\Support\Str;

class CommentRepository extends BaseRepository implements CommentInterface
{
    public function __construct(Comment $model)
    {
        parent::__construct($model);
    }
    public function getParentCommentsByPost(string $postId, int $limit = 20, array $relations = [])
    {
        return $this->model
            ->with($relations)
            ->where('post_id', $postId)
            ->whereNull('parent_id') // 👈 chỉ lấy comment cha
            ->latest()
            ->paginate($limit);
    }
    public function getByFieldWithPagination(
        string $field,
        $value,
        array $relations = [],
        int $limit,
    ) {
        return $this->model
            ->with($relations)
            ->where($field, $value)
            ->orderBy('updated_at', 'desc') // 👈 đổi từ latest() sang DESC
            ->orderBy('comment_id', 'desc') // để đảm bảo thứ tự nhất quán
            ->paginate($limit);
    }
}
