<?php

namespace App\Repositories\TagRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Tag;
use App\Models\PostMedia;
use Illuminate\Support\Str;

class TagRepository extends BaseRepository implements TagInterface
{
    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }

    public function upsertAndGetIds(array $tags): array
    {
        // Map thành dạng ['name' => ...]
        $insertData = array_map(fn($tag) => ['name' => $tag], $tags);

        // Upsert (chỉ insert nếu chưa có)
        Tag::upsert($insertData, ['name'], []);

        // Lấy lại danh sách ID từ DB
        return Tag::whereIn('name', $tags)->pluck('tag_id')->toArray();
    }
}
