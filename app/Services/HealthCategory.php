<?php

namespace App\Services;

use App\Repositories\HealthCategoryRepository\HealthCategoryInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Pet;
use Illuminate\Support\Facades\Log;

class HealthCategory
{
    protected $healthCategoryInterface;

    public function __construct(HealthCategoryInterface $healthCategoryInterface)
    {
        $this->healthCategoryInterface = $healthCategoryInterface;
    }

    public function getHealthCategoriesByUserId()
    {
        $userId = Auth::id();
        return $this->healthCategoryInterface->getByField('user_id', $userId, ['healthLogs'], [], 5);
    }
    public function createHealthCategory(array $data)
    {
        $data['user_id'] = Auth::id();
        $data['applicable_species'] = [Pet::findOrFail($data['pet_id'])->type];
        return $this->healthCategoryInterface->create($data);
    }
    public function updateHealthCategory(string $id, array $data)
    {
        return $this->healthCategoryInterface->update($id, $data);
    }
    public function deleteHealthCategory(string $id)
    {
        return $this->healthCategoryInterface->delete($id);
    }

    public function getHealthCategoriesBySystem(string $species)
    {
        return $this->healthCategoryInterface->getCategoriesBySpecies($species);
    }
    public function getHealthDataForPet(string $petId)
    {
        $pet = Pet::findOrFail($petId);

        $categories = $this->healthCategoryInterface
            ->getCategoriesWithLogsByPet($pet->type, $petId, $pet->user_id);

        return $categories;
    }
}
