<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PetHealthLogSeeder extends Seeder
{
    public function run(): void
    {
        $petId = '019a3fef-1e78-7282-a860-27e3c6757279'; // Pet ID test
        $now = now();

        // Lấy category_id theo tên (đã đảm bảo applicable_species có chứa "dog")
        $categories = DB::table('health_categories')
            ->whereIn('name', [
                'Cân nặng',
                'Chăm sóc răng miệng',
                'Chế độ ăn uống',
                'Da & lông',
                'Khám tổng quát',
                'Tẩy giun',
                'Tiêm phòng',
                'Vận động & tập luyện',
                'Môi trường sống',
            ])
            ->pluck('category_id', 'name'); // key = name, value = category_id

        $logs = [
            // --- Cân nặng ---
            ['category' => 'Cân nặng', 'title' => 'Cân 5.2kg', 'value' => '5.2 kg', 'recorded_at' => '2025-08-01', 'description' => 'Ổn định.'],
            ['category' => 'Cân nặng', 'title' => 'Cân 5.8kg', 'value' => '5.8 kg', 'recorded_at' => '2025-09-01', 'description' => 'Tăng nhẹ.'],
            ['category' => 'Cân nặng', 'title' => 'Cân 6.0kg', 'value' => '6.0 kg', 'recorded_at' => '2025-10-01', 'description' => 'Tăng đều, khỏe.'],

            // --- Khám tổng quát ---
            ['category' => 'Khám tổng quát', 'title' => 'Khám kỳ 6 tháng', 'recorded_at' => '2025-07-15', 'description' => 'Sức khỏe tốt, không bệnh.'],

            // --- Tiêm phòng ---
            ['category' => 'Tiêm phòng', 'title' => 'Tiêm vaccine dại', 'recorded_at' => '2025-09-20', 'description' => 'Không phản ứng sau tiêm.'],

            // --- Tẩy giun ---
            ['category' => 'Tẩy giun', 'title' => 'Tẩy giun lần 2', 'recorded_at' => '2025-10-05', 'value' => '1 viên Drontal', 'description' => 'Ăn tốt sau khi dùng.'],

            // --- Chế độ ăn uống ---
            ['category' => 'Chế độ ăn uống', 'title' => 'Chuyển khẩu phần', 'recorded_at' => '2025-10-10', 'description' => 'Chuyển sang Royal Canin size mini.'],

            // --- Da & lông ---
            ['category' => 'Da & lông', 'title' => 'Tắm & sấy', 'recorded_at' => '2025-10-18', 'description' => 'Lông mềm, mượt.'],

            // --- Vận động ---
            ['category' => 'Vận động & tập luyện', 'title' => 'Đi dạo sáng', 'recorded_at' => '2025-10-25', 'value' => '30 phút', 'description' => 'Tinh thần vui vẻ.'],

            // --- Môi trường sống ---
            ['category' => 'Môi trường sống', 'title' => 'Kiểm tra nhiệt độ phòng', 'recorded_at' => '2025-10-28', 'value' => '27°C - 60%RH', 'description' => 'Điều kiện tốt.'],
        ];

        $insert = [];

        foreach ($logs as $log) {
            $insert[] = [
                'log_id' => (string) Str::uuid(),
                'pet_id' => $petId,
                'category_id' => $categories[$log['category']] ?? null,
                'title' => $log['title'],
                'description' => $log['description'] ?? null,
                'value' => $log['value'] ?? null,
                'image_url' => null,
                'recorded_at' => $log['recorded_at'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('pet_health_logs')->insert($insert);
    }
}
