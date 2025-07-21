<?php
namespace App\Repositories\AuthRepository;

interface AuthInterface
{
    public function register(array $data);
    public function login(array $data);
    public function logout(): void;
}
