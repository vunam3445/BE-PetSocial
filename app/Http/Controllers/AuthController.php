<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Requests\AuthRequest;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AuthController extends Controller
{
    use AuthorizesRequests;
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register(AuthRequest $request)
    {
        $data = $request->validated();
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
        $result = $this->authService->login($data);

        if ($result) {
            return response()->json([
                'access_token' => $result['token'],
                'token_type'   => 'bearer',
                'expires_in'   => JWTAuth::factory()->getTTL() * 60,
                'user'         => $result['user'],
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
    public function confirmRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $user = $this->authService->confirmRegister($request->all());

        if ($user) {
            return response()->json($user);
        }

        return response()->json(['message' => 'OTP không hợp lệ hoặc đã hết hạn'], 400);
    }
    public function sendOtp(Request $request)
    {
        $request->validate(
            ['email' => 'required|email'],
            [
                'email.required' => 'Vui lòng nhập địa chỉ email.',
                'email.email' => 'Địa chỉ email không đúng định dạng.',
            ]
        );

        $this->authService->sendOtp($request->all());
        return response()->json(['message' => 'Đã gửi mã OTP'], 200);
    }

    // AuthController.php
    public function changePassword(Request $request, string $userId)
    {
        // 1. Tìm user mục tiêu dựa trên ID truyền vào
        $targetUser = User::findOrFail($userId);

        // 2. Kiểm tra Policy (Sẽ ném ra lỗi 403 nếu không phải chính chủ)
        $this->authorize('update', $targetUser);
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'confirm_password.same' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $userId = Auth::id(); // Lấy ID từ JWT Token
        $result = $this->authService->changePassword($userId, $request->all());

        if (!$result['status']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => $result['message']], 200);
    }

  public function confirmOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp'   => 'required|digits:6',
    ]);

    $result = $this->authService->confirmOtp($request->all());

    // Dòng 156 sẽ hoạt động nếu $result là mảng
    if (isset($result['status']) && $result['status']) {
        return response()->json([
            'message' => $result['message']
        ], 200);
    }

    return response()->json([
        'message' => $result['message'] ?? 'Xác thực thất bại'
    ], 400);
}
    // 2. Hàm đặt lại mật khẩu mới
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'            => 'required|email',
            'new_password'     => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.min'      => 'Mật khẩu mới phải từ 6 ký tự.',
            'confirm_password.same' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $result = $this->authService->resetPassword($request->all());

        if ($result['status']) {
            return response()->json([
                'message' => $result['message']
            ], 200);
        }

        return response()->json([
            'message' => $result['message']
        ], 400);
    }
}
