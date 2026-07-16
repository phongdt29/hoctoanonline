<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/** Ticket R3 — goi cuoc mau. */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Gói 1 tháng',
                'price' => 199000,
                'duration_days' => 30,
                'features' => ['Lộ trình cá nhân hóa', 'Gia sư A.I không giới hạn', 'Giải bài từ ảnh', 'Phụ huynh theo dõi'],
                'is_active' => true,
            ],
            [
                'name' => 'Gói 3 tháng',
                'price' => 499000,
                'duration_days' => 90,
                'features' => ['Tất cả của gói 1 tháng', 'Tiết kiệm 16%', 'Báo cáo tuần chi tiết'],
                'is_active' => true,
            ],
            [
                'name' => 'Gói 1 năm',
                'price' => 1590000,
                'duration_days' => 365,
                'features' => ['Tất cả của gói 3 tháng', 'Tiết kiệm 33%', 'Ưu tiên hỗ trợ'],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
