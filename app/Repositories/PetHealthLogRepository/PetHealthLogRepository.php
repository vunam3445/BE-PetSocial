<?php
namespace App\Repositories\PetHealthLogRepository;
use App\Models\PetHealthLog;
use App\Repositories\Base\BaseRepository;
class PetHealthLogRepository extends BaseRepository implements PetHealthLogInterface
{
    public function __construct(PetHealthLog $model)
    {
        parent::__construct($model);
    }
    
public function getLogsByPetId(string $petId)
{
    return $this->model
        ->where('pet_id', $petId)
        ->with(['healthLogs' => function($query) {
            $query->orderBy('created_at', 'desc'); // Sắp xếp log bên trong category
        }])
        ->get();
}
  
}