<?php

namespace App\Repositories\LikeRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Like;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class LikeRepository extends BaseRepository implements LikeInterface
{
    public function __construct(Like $model)
    {
        parent::__construct($model);
    }

    public function getLikesForPost(string $postId)
    {
        return $this->model->where('post_id', $postId)->count();
    }
    
}
