<?php

namespace App\Repositories\AuthRepository;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        // Thử đăng nhập với thông tin email và password
        if (! $token = JWTAuth::attempt($data)) {
            // Cách 1: Ném ra lỗi Validation (Khuyên dùng)
            // Laravel sẽ tự động trả về lỗi 422 với message này
            throw ValidationException::withMessages([
                'login' => ['Tài khoản hoặc mật khẩu không chính xác.'],
            ]);
        }

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
    public function changePassword(string $userId, array $data)
    {
        $user = User::find($userId);

        if (!$user) {
            return ['status' => false, 'message' => 'Người dùng không tồn tại.'];
        }

        // Kiểm tra mật khẩu cũ
        if (!Hash::check($data['old_password'], $user->password)) {
            return ['status' => false, 'message' => 'Mật khẩu hiện tại không chính xác.'];
        }

        // Cập nhật mật khẩu mới
        $user->password = bcrypt($data['new_password']);
        $user->save();

        return ['status' => true, 'message' => 'Đổi mật khẩu thành công.'];
    }

    public function confirmOtp(array $data)
{
    $email = $data['email'] ?? null;
    $otpInput = $data['otp'] ?? null;

    $cachedOtp = Cache::get("otp_{$email}");

    if ($cachedOtp && $cachedOtp == $otpInput) {
        // Cấp thẻ thông hành
        Cache::put("verified_email_{$email}", true, now()->addMinutes(10));
        
        // PHẢI TRẢ VỀ MẢNG NHƯ THẾ NÀY
        return [
            'status' => true,
            'message' => 'Xác thực mã OTP thành công.'
        ];
    }

    return [
        'status' => false,
        'message' => 'Mã OTP không chính xác hoặc đã hết hạn.'
    ];
}

    public function resetPassword(array $data)
    {
        $email = $data['email'];

        // Kiểm tra xem đã qua bước xác thực OTP chưa
        if (!Cache::has("verified_email_{$email}")) {
            return ['status' => false, 'message' => 'Bạn chưa xác thực mã OTP hoặc phiên làm việc đã hết hạn.'];
        }

        // Nếu có "thẻ thông hành", tiến hành đổi mật khẩu
        $user = User::where('email', $email)->first();
        $user->password = bcrypt($data['new_password']);
        $user->save();

        // Đổi xong thì hủy thẻ luôn
        Cache::forget("verified_email_{$email}");

        return ['status' => true, 'message' => 'Mật khẩu đã được cập nhật.'];
    }
}
