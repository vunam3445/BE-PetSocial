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
    
        return User::create([
            'email' => $email,
            'password' => bcrypt($password),
            'name' => $name,
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
