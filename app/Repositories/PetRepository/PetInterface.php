<?php
namespace App\Repositories\PetRepository;

interface PetInterface
{
    public function createPet(array $data);

    public function getPet(string $petId): array;

    public function updatePet(string $petId, array $data): bool;

    public function deletePet(string $petId): bool;
    public function getAllPetsByUser(?string $userId): array; // Optional: If you want to retrieve all pets
}
