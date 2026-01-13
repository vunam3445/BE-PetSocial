<?php

namespace App\Repositories\HealthCategoryRepository;

use App\Repositories\HealthCategoryRepository\HealthCategoryInterface;
use App\Models\HealthCategory;
use App\Repositories\Base\BaseRepository;

class HealthCategoryRepository extends BaseRepository implements HealthCategoryInterface
{
    public function __construct(HealthCategory $model)
    {
        parent::__construct($model);
    }
    public function getCategoriesWithLogsByPet(string $species, string $petId, string $userId)
    {
        return $this->model
            ->whereJsonContains('applicable_species', $species)
            ->where(function ($query) use ($petId, $userId) {
                $query
                    // 1️⃣ Category của hệ thống (không user_id, không pet_id)
                    ->where(function ($q) {
                        $q->whereNull('user_id')->whereNull('pet_id');
                    })
                    // 2️⃣ Category của user tạo riêng cho pet này
                    ->orWhere(function ($q) use ($userId, $petId) {
                        $q->where('user_id', $userId)
                            ->where('pet_id', $petId);
                    })
                    // 3️⃣ Category có log của pet này (dù hệ thống hay user)
                    ->orWhereHas('healthLogs', function ($q) use ($petId) {
                        $q->where('pet_id', $petId);
                    });
            })
            ->with(['healthLogs' => function ($query) use ($petId) {
                $query->where('pet_id', $petId)->orderBy('recorded_at', 'desc');
            }])
            ->orderBy('name')
            ->get();
    }

    public function getCategoriesBySpecies(string $species)
    {
        return $this->model
            ->whereJsonContains('applicable_species', $species)
            ->orderBy('name')
            ->take(5)
            ->get();
    }
}
