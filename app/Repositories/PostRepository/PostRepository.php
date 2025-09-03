<?php

namespace App\Repositories\PostRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Support\Str;
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
}
