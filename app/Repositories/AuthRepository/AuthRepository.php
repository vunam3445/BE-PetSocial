<?php

namespace App\Repositories\AuthRepository;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;
class AuthRepository implements AuthInterface
{
    public function register(array $data)
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $name = $data['name'] ?? null;
        $avatar_url = Storage::url('avatars/default-avatar.webp');
        $cover_url  = Storage::url('avatars/default-avatar.webp');
        $date_of_birth = $data['date_of_birth'] ?? null;
        $gender = $data['gender'] ?? null;

        return User::create([
            'email' => $email,
            'password' => bcrypt($password),
            'name' => $name,
            'avatar_url' => $avatar_url,
            'cover_url' => $cover_url,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender,
        ]);
    }


    public function login(array $data)
    {
        if (! $token = JWTAuth::attempt($data)) {
            return null;
        }

        // Lấy user từ JWTAuth
        $user = JWTAuth::user();

        return [
            'token' => $token,
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ],
        ];
    }



    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
}
