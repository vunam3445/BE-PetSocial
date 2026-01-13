<?php

namespace App\Repositories\PostRepository;

use App\Repositories\Base\BaseInterface;

interface PostInterface extends BaseInterface
{
    public function getQuery();
    public function getAllPosts(
        array $relations = [],
        array $withCount = [],
        int $limit = 20
    );
    public function getPostsOfGroup(
        string $groupId,
        array $relations = [],
        array $withCount = [],
        int $limit = 20
    );
        public function getPostsPendingOfGroup(
        string $groupId,
        array $relations = [],
        array $withCount = [],
        int $limit = 20
    );
}
