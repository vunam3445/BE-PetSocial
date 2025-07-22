<?php
namespace App\Repositories\PetRepository;
use App\Models\Pet;
class PetRepository implements PetInterface
{
    public function createPet(array $data)
    {
        // Logic to create a pet
        $pet = new Pet($data);
        $pet->save();
        return $pet;
    }

    public function getPet(string $petId): array
    {
        // Logic to retrieve a pet by ID
        $pet = Pet::find($petId);
        if ($pet) {
            return $pet->toArray();
        }
        return [];
    }

    public function updatePet(string $petId, array $data): bool
    {
        // Logic to update a pet's information
        $pet = Pet::find($petId);
        if (!$pet) {
            return false;
        }
        $pet->fill($data);
        return $pet->save();
    }

    public function deletePet(string $petId): bool
    {
        // Logic to delete a pet by ID
        $pet = Pet::find($petId);
        if (!$pet) {
            return false;
        }
        return $pet->delete();
    }

    public function getAllPetsByUser(?string $userId): array
    {
        $pets = Pet::where('user_id', $userId)->get();
        return $pets->toArray();
    }
}