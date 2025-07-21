<?php
namespace App\Services;
use App\Repositories\AuthRepository\AuthInterface;
class AuthService
{
    public function __construct(private AuthInterface $authRepository)
    {
    }

    public function register(array $data)
    {
        return $this->authRepository->register($data);
    }

    public function login(array $data)
    {
        
    }

    public function logout(): void
    {
        
    }
}
