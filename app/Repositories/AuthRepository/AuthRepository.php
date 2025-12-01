<?php

namespace App\Repositories\AuthRepository;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class AuthRepository implements AuthInterface
{
    public function register(array $data)
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $name = $data['name'] ?? null;
        $avatar_url = url(Storage::url('avatars/default-avatar.webp'));
        $cover_url  = url(Storage::url('avatars/default-avatar.webp'));

        $date_of_birth = $data['date_of_birth'] ?? null;
        $gender = $data['gender'] ?? null;

        return User::create([
            'email' => $email,
            'password' => bcrypt($password),
            'name' => $name,
            'avatar_url' => $avatar_url,
            'cover_url' => $cover_url,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender,
        ]);
    }


    public function login(array $data)
    {
        if (! $token = JWTAuth::attempt($data)) {
            return null;
        }

        // Lấy user từ JWTAuth
        $user = JWTAuth::user();

        return [
            'token' => $token,
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ],
        ];
    }



    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function sendOtp(array $data)
    {
        $email = $data['email'];

        // Sinh OTP ngẫu nhiên
        $otp = rand(100000, 999999);

        // Lưu OTP vào cache (key theo email), hết hạn sau 5 phút
        Cache::put("otp_{$email}", $otp, now()->addMinutes(5));

        // Gửi mail OTP
        Mail::to($email)->send(new OtpMail($otp));

        return true;
    }

    public function verifyOtp(array $data)
    {
        $email = $data['email'];
        $otpInput = $data['otp'];

        $cachedOtp = Cache::get("otp_{$email}");

        if ($cachedOtp && $cachedOtp == $otpInput) {
            // Xoá OTP trong cache sau khi dùng
            Cache::forget("otp_{$email}");
            return true;
        }

        return false;
    }
    public function confirmRegister(array $data)
    {
        $email = $data['email'];
    $otpInput = $data['otp'];

    $cacheOtp = Cache::get("otp_{$email}");

    if (!$cacheOtp || $cacheOtp != $otpInput) {
        return null; // sai OTP
    }


        // Tạo user
        $user = User::create([
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'name' => $data['name'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'avatar_url' => url('storage/avatars/default-avatar.webp'),
            'cover_url' => url('storage/avatars/default-avatar.webp'),
        ]);

        // Xoá cache sau khi dùng
        Cache::forget("register_{$email}");

        return $user;
    }
}
