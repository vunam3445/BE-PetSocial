<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            DB::table('users')->insert([
                'user_id' => (string) Str::uuid(), // Tạo UUID tự động
                'name' => "Test User $i",
                'email' => "test$i@gmail.com",
                'password' => Hash::make('123456'), // Mật khẩu là 'password'
                'avatar_url' => "https://api.dicebear.com/7.x/avataaars/svg?seed=test$i", // Ảnh mẫu ngẫu nhiên
                'cover_url' => null,
                'bio' => "Hello, I am test user number $i",
                'date_of_birth' => '2000-01-0' . $i,
                'gender' => $i % 2 == 0 ? 'female' : 'male', // Xen kẽ nam nữ cho đa dạng
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}