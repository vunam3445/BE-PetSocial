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
        return $this->authRepository->login($data);
    }

    public function logout(): void
    {
        $this->authRepository->logout();
    }
}
