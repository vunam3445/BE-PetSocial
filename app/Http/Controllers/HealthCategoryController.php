<?php

namespace App\Http\Controllers;

use App\Services\HealthCategory;
use Illuminate\Http\Request;

class HealthCategoryController extends Controller
{
    protected HealthCategory $healthCategoryService;

    public function __construct(HealthCategory $healthCategoryService)
    {
        $this->healthCategoryService = $healthCategoryService;
    }

    public function index(string $petId)
    {
        $healthCategories = $this->healthCategoryService->getHealthDataForPet($petId);
        return response()->json($healthCategories);
    }
    public function store(Request $request)
    {
        $data = $request->all();
        $healthCategory = $this->healthCategoryService->createHealthCategory($data);
        return response()->json($healthCategory, 201);
    }
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $healthCategory = $this->healthCategoryService->updateHealthCategory($id, $data);
        return response()->json($healthCategory);
    }
    public function destroy(string $id)
    {
        $this->healthCategoryService->deleteHealthCategory($id);
        return response()->json(['message' => 'Health category deleted successfully']);
    }
    public function getBySpecies(string $species)
    {
        $categories = $this->healthCategoryService->getHealthCategoriesBySystem($species);
        return response()->json($categories);
    }
}
