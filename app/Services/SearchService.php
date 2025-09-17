<?php

namespace App\Services;

use App\Repositories\SearchRepository\SearchInterface;
class SearchService
{
    protected SearchInterface $searchRepository;

    public function __construct(SearchInterface $searchRepository)
    {
        $this->searchRepository = $searchRepository;
    }

    public function search(string $type, string $keyword, int $limit = 10)
    {
       switch ($type) {
    case 'user':
        return $this->searchRepository->searchUsers($keyword, $limit);

    case 'post':
        return $this->searchRepository->searchPosts($keyword, $limit);

    case 'pet':
        return $this->searchRepository->searchPets($keyword, $limit);

    default:
        return response()->json([
            'message' => 'Invalid search type',
        ], 400);
}
    }
}
