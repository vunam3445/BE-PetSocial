<?php

namespace App\Repositories\AuthRepository;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthRepository implements AuthInterface
{
    public function register(array $data)
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $name = $data['name'] ?? null;
        $avatar_url = 'text'; // Default value, can be changed based on requirements
        $cover_url = 'text';
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
        
        if (!$token=JWTAuth::attempt($data)) {
            return null;
        }

        return $token; 
    }

    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
}
