<?php

namespace App\Repositories\HealthCategoryRepository;
use App\Repositories\Base\BaseInterface;

interface HealthCategoryInterface extends BaseInterface
{
  public function getCategoriesWithLogsByPet(string $species, string $petId, string $userId);
  public function getCategoriesBySpecies(string $species);
}