<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profileService)
    {
    }

    /**
     * Update the user's profile.
     */
    public function update(UpdateProfileRequest $request)
    {


        $data = $request->validated();
        $userId = Auth::id();
        $res = $this->profileService->updateProfile($userId, $data);

        return response()->json(['message' => 'Profile updated successfully.',
                            'user' => $res
    ]);
    }

    /**
     * Get the user's profile.
     */
    public function show(string $userId)
    {
        $user = $this->profileService->getProfile($userId);
        if (empty($user)) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }
        return response()->json($user);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request)
    {
        // Logic to delete user profile
    }

    public function getMedia(string $userId, string $mediaType)
    {
        $media = $this->profileService->getMedia($userId, $mediaType);
        return response()->json($media);
    }
}
