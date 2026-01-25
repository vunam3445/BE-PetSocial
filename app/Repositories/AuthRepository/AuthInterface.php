<?php

namespace App\Repositories\AuthRepository;

interface AuthInterface
{
    public function register(array $data);
    public function login(array $data);
    public function logout(): void;
    public function sendOtp(array $data);
    public function verifyOtp(array $data);
    public function confirmRegister(array $data);
    public function changePassword(string $userId, array $data);
    public function confirmOtp(array $data);
    public function resetPassword(array $data);
}
