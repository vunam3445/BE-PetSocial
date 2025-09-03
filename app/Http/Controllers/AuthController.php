<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Requests\AuthRequest;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register(AuthRequest $request)
    {
        $data = $request->validated();
        Log::info('Registering user with data: ', $data);
        $user = $this->authService->register($data);
        if ($user) {
            return response()->json([
                'message' => 'User registered successfully',

            ], 201);
        }
        return response()->json([
            'message' => 'User registration failed'
        ], 400);
    }

    public function login(AuthLoginRequest $request)
    {
        $data = $request->validated();
        $token = $this->authService->login($data);
        if ($token) {
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => \Tymon\JWTAuth\Facades\JWTAuth::factory()->getTTL() * 60
            ]);
        }
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }
    public function logout(Request $request)
    {
        try {
            $this->authService->logout();
            return response()->json(['message' => 'User logged out successfully']);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json(['message' => 'Logout failed'], 500);
        }
    }


    public function refresh(Request $request)
    {
        try {
              $token = \Tymon\JWTAuth\Facades\JWTAuth::getToken();
        if (!$token) {
            return response()->json(['message' => 'Token not found in request']);
        }
            $newToken = \Tymon\JWTAuth\Facades\JWTAuth::refresh($token); // Tự lấy token từ header
            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => \Tymon\JWTAuth\Facades\JWTAuth::factory()->getTTL() * 60
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token not provided'], 401);
        }
    }
}
