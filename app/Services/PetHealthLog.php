<?php

namespace App\Services;
use App\Repositories\PetHealthLogRepository\PetHealthLogInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Pet;
class PetHealthLog
{
    protected $petHealthLogInterface;

    public function __construct(PetHealthLogInterface $petHealthLogInterface)
    {
        $this->petHealthLogInterface = $petHealthLogInterface;
    }

    public function createPetHealthLog(array $data)
    {
        $pet_id = $data['pet_id'] ?? null;
        $user_id = Auth::id();
        $ownedPet = Pet::findOrFail($pet_id);
        if ($ownedPet->user_id !== $user_id) {
            throw new \Exception("Unauthorized: You do not own this pet.");
        }
        return $this->petHealthLogInterface->create($data);
    }
    public function updatePetHealthLog(string $id, array $data)
    {
        return $this->petHealthLogInterface->update($id, $data);
    }
    public function deletePetHealthLog(string $id)
    {
        return $this->petHealthLogInterface->delete($id);
    }
    public function getPetHealthLogsByPetId(string $petId)
    {
        return $this->petHealthLogInterface->getByField('pet_id', $petId);
    }

    public function getLogsByPetId(string $petId)
    {
        return $this->petHealthLogInterface->getLogsByPetId($petId);
    }
}