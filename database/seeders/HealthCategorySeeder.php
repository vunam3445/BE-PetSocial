<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HealthCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // 🐶🐱 CHUNG CHO CHÓ + MÈO
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Tiêm phòng',
                'description' => 'Theo dõi lịch tiêm phòng: dại, care, parvo...',
                'applicable_species' => json_encode(['dog', 'cat']),
                'category_type' => 'schedule',
                'interval_days' => 365,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Tẩy giun',
                'description' => 'Theo dõi lịch tẩy giun định kỳ.',
                'applicable_species' => json_encode(['dog', 'cat']),
                'category_type' => 'schedule',
                'interval_days' => 180,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Cân nặng',
                'description' => 'Theo dõi cân nặng qua từng giai đoạn.',
                'applicable_species' => json_encode(['dog', 'cat']),
                'category_type' => 'metric', // 📈 Có biểu đồ
                'interval_days' => null,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Khám tổng quát',
                'description' => 'Ghi lại lịch khám và kết quả.',
                'applicable_species' => json_encode(['dog', 'cat']),
                'category_type' => 'event', // 🗓️ Timeline
                'interval_days' => null,

            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Chế độ ăn uống',
                'description' => 'Theo dõi khẩu phần ăn và chế độ dinh dưỡng.',
                'applicable_species' => json_encode(['dog', 'cat', 'rabbit', 'lizard']),
                'category_type' => 'note',
                'interval_days' => null,
            ],

            // 🐶 CHÓ RIÊNG
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Chăm sóc răng miệng',
                'description' => 'Theo dõi lịch chăm sóc răng miệng.',
                'applicable_species' => json_encode(['dog']),
                'category_type' => 'event',
                'interval_days' => null,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Vận động & tập luyện',
                'description' => 'Theo dõi lịch vận động hằng ngày.',
                'applicable_species' => json_encode(['dog']),
                'category_type' => 'note',
                'interval_days' => null,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Da & lông',
                'description' => 'Theo dõi lông và da để phát hiện dị ứng / nấm.',
                'applicable_species' => json_encode(['dog']),
                'category_type' => 'event',
                'interval_days' => null,
            ],

            // 🐱 MÈO RIÊNG
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Chăm sóc lông',
                'description' => 'Theo dõi lịch tắm, chải lông, tỉa móng.',
                'applicable_species' => json_encode(['cat']),
                'category_type' => 'event',
                'interval_days' => null,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Hộp cát & vệ sinh',
                'description' => 'Theo dõi tình trạng đi vệ sinh.',
                'applicable_species' => json_encode(['cat']),
                'category_type' => 'event',
                'interval_days' => null,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Tinh thần & hành vi',
                'description' => 'Ghi nhận thay đổi trong hành vi.',
                'applicable_species' => json_encode(['cat']),
                'category_type' => 'note',
                'interval_days' => null,
            ],

            // 🦎 BÒ SÁT / THÚ CƯNG ĐẶC BIỆT
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Môi trường sống',
                'description' => 'Theo dõi nhiệt độ & độ ẩm.',
                'applicable_species' => json_encode(['lizard', 'reptile', 'snake']),
                'category_type' => 'metric', // Có thể vẽ biểu đồ độ ẩm/nhiệt độ
                'interval_days' => null,
            ],
            [
                'category_id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Lột da / Thay vảy',
                'description' => 'Theo dõi chu kỳ phát triển.',
                'applicable_species' => json_encode(['lizard', 'snake']),
                'category_type' => 'event',
                'interval_days' => null,
            ],
        ];

        DB::table('health_categories')->insert($categories);
    }
}
