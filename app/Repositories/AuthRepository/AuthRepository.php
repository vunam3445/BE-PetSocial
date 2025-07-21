<?php

namespace App\Repositories\AuthRepository;

use App\Models\User;

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
        // Logic for user login
    }

    public function logout(): void
    {
        // Logic for user logout
    }
}
