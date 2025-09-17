<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\PetService;
use App\Http\Requests\CreatePetRequest;
use App\Http\Requests\UpdatePetRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class PetController extends Controller
{
    private $petService;

    public function __construct(PetService $petService)
    {
        $this->petService = $petService;
    }

    public function create(CreatePetRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id(); // Assuming the user is authenticated and you want to associate the pet with the user
        $result = $this->petService->createPet($data);
        if ($result) {
            return response()->json(['message' => 'Pet created successfully',
            'pet' => $result
        ], 201);
        }
        return response()->json(['message' => 'Failed to create pet'], 400);
    }
    public function show(string $petId)
    {
        $pet = $this->petService->getPet($petId);
        if (empty($pet)) {
            return response()->json(['message' => 'Pet not found'], 404);
        }
        return response()->json($pet); 
    }

    public function update(string $petId, UpdatePetRequest $request)
    {
        $data = $request->validated();
        Log::info('kdaasa',$request->All());
        $result = $this->petService->updatePet($petId, $data);
        if ($result) {
            return response()->json(['message' => 'Pet updated successfully'], 200);
        }
        return response()->json(['message' => 'Failed to update pet'], 400);
    }

    public function delete(string $petId)
    {
        $result = $this->petService->deletePet($petId);
        if ($result) {
            return response()->json(['message' => 'Pet deleted successfully'], 200);
        }
        return response()->json(['message' => 'Failed to delete pet'], 400);
    }
    public function getAllPetsByUser(string $userId)
    {
        $pets = $this->petService->getAllPetsByUser($userId);
        return response()->json($pets);
    }
}
