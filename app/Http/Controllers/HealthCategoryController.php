<?php

namespace App\Http\Controllers;

use App\Services\HealthCategory;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class HealthCategoryController extends Controller
{
    use AuthorizesRequests;
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
        $petId = $request->input('pet_id');
        $pet = \App\Models\Pet::findOrFail($petId);
        // 2. Kiểm tra quyền 'create' (Bạn là chủ của Pet này thì mới được tạo Category cho nó)
        $this->authorize('create', [\App\Models\HealthCategory::class,$pet]);
        $data = $request->all();
        $healthCategory = $this->healthCategoryService->createHealthCategory($data);
        return response()->json($healthCategory, 201);
    }
    public function update(Request $request, string $id)
    {
        $category = \App\Models\HealthCategory::with('pet')->findOrFail($id);

        // 2. Kiểm tra quyền 'update' trong PetHealthPolicy
        $this->authorize('update', $category);
        $data = $request->all();
        $healthCategory = $this->healthCategoryService->updateHealthCategory($id, $data);
        return response()->json($healthCategory);
    }
    public function destroy(string $id)
    {
        $category = \App\Models\HealthCategory::with('pet')->findOrFail($id);

        // 2. Kiểm tra quyền 'delete'
        $this->authorize('delete', $category);
        $this->healthCategoryService->deleteHealthCategory($id);
        return response()->json(['message' => 'Health category deleted successfully']);
    }
    public function getBySpecies(string $species)
    {
        $categories = $this->healthCategoryService->getHealthCategoriesBySystem($species);
        return response()->json($categories);
    }
}
