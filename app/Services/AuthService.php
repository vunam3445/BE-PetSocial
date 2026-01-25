<?php

namespace App\Services;

use App\Repositories\AuthRepository\AuthInterface;

class AuthService
{
    public function __construct(private AuthInterface $authRepository) {}

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

    public function sendOtp(array $data)
    {
        return $this->authRepository->sendOtp($data);
    }

    public function verifyOtp(array $data)
    {
        return $this->authRepository->verifyOtp($data);
    }
    public function confirmRegister(array $data)
    {
        return $this->authRepository->confirmRegister($data);
    }

    public function changePassword(string $userId, array $data)
    {
        return $this->authRepository->changePassword($userId, $data);
    }
    public function resetPassword(array $data)
    {
        return $this->authRepository->resetPassword($data);
    }
    public function confirmOtp(array $data)
    {
        return $this->authRepository->confirmOtp($data);
    }
}
