<?php

namespace App\Repositories\SearchRepository;

interface SearchInterface
{
    public function searchUsers(string $keyword, int $limit = 10);
    public function searchPosts(string $keyword, int $limit = 10);
    public function searchPets(string $keyword, int $limit = 10);
    public function searchGroups(string $keyword, int $limit =  10);
    public function searchJoinGroupRequest(string $groupId, string $keyword, int $limit = 20);
    public function searchUsersOfGroup(string $groupId, string $keyword, int $limit = 20);
    public function searchConversation(string $keyword, int $limit = 15);
}
