<?php
namespace App\Repositories\GroupRepository;
use App\Models\Group;
interface GroupInterface {
    public function create(array $data): Group;
    public function update(Group $group, array $data): Group;
    public function delete(Group $group): bool;
    public function getGroupsByUserId(string $userId, int $perPage = 10);
    public function getGroupOfUserAttended(string $userId, int $perPage = 10);
    public function addMemberToGroup(string $groupId, array $userIds, string $role): void;
    public function getGroupById(string $groupId, string $userId);
    public function getMembers(string $groupId, int $limit =20);
    public function find(string $groupId);
}