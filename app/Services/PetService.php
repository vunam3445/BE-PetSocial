<?php
namespace App\Services;
use App\Repositories\PetRepository\PetInterface;
use Illuminate\Support\Facades\Auth;
class PetService
{
    private $petRepository;

    public function __construct(PetInterface $petRepository)
    {
        $this->petRepository = $petRepository;
    }

    public function createPet(array $data)
    {
        return $this->petRepository->createPet($data);
    }

    public function getPet(string $petId): array
    {
        return $this->petRepository->getPet($petId);
    }

    public function updatePet(string $petId, array $data): bool
    {
        return $this->petRepository->updatePet($petId, $data);
    }

    public function deletePet(string $petId): bool
    {
        return $this->petRepository->deletePet($petId);
    }

    public function getAllPetsByUser(?string $userId): array
    {
        if($userId) {
            return $this->petRepository->getAllPetsByUser($userId);
        }
        $user= Auth::user();
        $pets = $user->pets; // Assuming the User model has a pets relationship
        return $pets->toArray();
    }
}