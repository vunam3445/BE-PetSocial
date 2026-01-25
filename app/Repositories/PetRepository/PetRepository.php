<?php

namespace App\Repositories\PetRepository;

use App\Models\Pet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetRepository implements PetInterface
{
    public function createPet(array $data)
    {
        // Logic to create a pet
        $pet = new Pet($data);
        $pet->save();
        return $pet;
    }

    public function getPet(string $petId): array
    {
        // Logic to retrieve a pet by ID
        $userId = Auth::id();
        $pet = Pet::withCount('followers')
            ->addSelect([
                'is_following' => DB::table('pet_follows')
                    ->whereColumn('pet_follows.pet_id', 'pets.pet_id')
                    ->where('pet_follows.user_id', $userId)
                    ->selectRaw('count(*) > 0')
            ])
            ->findOrFail($petId);
        if ($pet) {
            return $pet->toArray();
        }
        return [];
    }

    public function updatePet(string $petId, array $data): bool
    {
        // Logic to update a pet's information
        $pet = Pet::find($petId);
        if (!$pet) {
            return false;
        }
        $pet->fill($data);
        return $pet->save();
    }

    public function deletePet(string $petId): bool
    {
        // Logic to delete a pet by ID
        $pet = Pet::find($petId);
        if (!$pet) {
            return false;
        }
        return $pet->delete();
    }

    public function getAllPetsByUser(string $userId): array
    {
        $pets = Pet::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        return $pets->toArray();
    }
    public function getAllPostsByPet(string $petId, $limit)
{
    $ownerId = Auth::id();
    $pet = Pet::findOrFail($petId);

    return $pet->posts()
        ->with([
            'media',
            'author:user_id,name,avatar_url',
            'tags',
            'pet:pet_id,name,avatar_url'
        ])
        ->withCount([
            'likes as is_liked' => function ($q) use ($ownerId) {
                $q->where('user_id', $ownerId);
            },
            'likes as likes_count',
        ])
        // ✅ Thêm logic kiểm tra quyền riêng tư
        ->where(function ($query) use ($ownerId) {
            $query->where('author_id', $ownerId) // Nếu là chủ post thì thấy hết
                  ->orWhere('visibility', 'public'); // Nếu không phải chủ thì chỉ thấy public
        })
        ->selectRaw('*, (author_id = ?) as is_owner', [$ownerId])
        ->orderBy('created_at', 'desc')
        ->paginate($limit)
        ->toArray();
}
}
