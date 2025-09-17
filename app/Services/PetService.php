<?php

namespace App\Services;

use App\Repositories\PetRepository\PetInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PetService
{
    private $petRepository;

    public function __construct(PetInterface $petRepository)
    {
        $this->petRepository = $petRepository;
    }

    public function createPet(array $data)
    {
        if (isset($data['avatar_url']) && $data['avatar_url'] instanceof \Illuminate\Http\UploadedFile) {
            $avatarPath = $data['avatar_url']->store('pets', 'public');
            Log::info("Avatar stored at: " . $avatarPath);
            $data['avatar_url'] = asset('storage/' . $avatarPath);
        } else {
            Log::warning("Avatar not uploaded or invalid");
        }

        return $this->petRepository->createPet($data);
    }


    public function getPet(string $petId): array
    {
        return $this->petRepository->getPet($petId);
    }

    public function updatePet(string $petId, array $data): bool
    {
        $pet = $this->petRepository->getPet($petId);
        if (!$pet) {
            throw new \Exception('Pet not found');
        }
        $userId = Auth::id();
        if ($pet['user_id'] !== $userId) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Bạn không có quyền sửa pet này.');
        }
        // Xử lý ảnh avatar nếu có
        if (isset($data['avatar_url'])) {
            $avatarPath = $data['avatar_url']->store('pets', 'public');
            $data['avatar_url'] = asset('storage/' . $avatarPath);
        }
        return $this->petRepository->updatePet($petId, $data);
    }

    public function deletePet(string $petId): bool
    {
        return $this->petRepository->deletePet($petId);
    }

    public function getAllPetsByUser(string $userId): array
    {
        return $this->petRepository->getAllPetsByUser($userId);
    }
}
