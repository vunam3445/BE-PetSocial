<?php

namespace App\Repositories\SearchRepository;

interface SearchInterface
{
    public function searchUsers(string $keyword, int $limit = 10);
    public function searchPosts(string $keyword, int $limit = 10);
    public function searchPets(string $keyword, int $limit = 10);
}
