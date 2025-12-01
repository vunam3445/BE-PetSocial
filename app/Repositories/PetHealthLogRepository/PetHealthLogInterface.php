<?php

namespace App\Repositories\PetHealthLogRepository;
use App\Repositories\Base\BaseInterface;

interface PetHealthLogInterface extends BaseInterface
{
    public function getLogsByPetId(string $petId);
}