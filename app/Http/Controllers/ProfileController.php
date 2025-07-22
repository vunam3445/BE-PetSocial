<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProfileService;
class ProfileController extends Controller
{
    public function __construct(private ProfileService $profileService)
    {
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'bio' => 'nullable|string|max:1000',
        ]);

        $userId = $request->user()->id;
        $this->profileService->updateProfile($userId, $data);

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    /**
     * Get the user's profile.
     */
    public function show(Request $request)
    {
        // Logic to get user profile
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request)
    {
        // Logic to delete user profile
    }
}
