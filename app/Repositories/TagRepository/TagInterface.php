<?php

namespace App\Repositories\TagRepository;

use App\Repositories\Base\BaseInterface;

interface TagInterface extends BaseInterface
{

    public function upsertAndGetIds(array $tags): array;
}
