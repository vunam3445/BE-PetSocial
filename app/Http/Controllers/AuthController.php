<?php

namespace App\Http\Controllers;

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
        $data = $request ->validated();
        $user = $this->authService->register($data);
        if($user) {
            return response()->json([
                'message' => 'User registered successfully',
            
            ], 201);
        }
        return response()->json([
            'message' => 'User registration failed'
        ], 400);
    }
 
}
