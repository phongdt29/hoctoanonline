<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Ticket F4 — du lieu dev.
 *
 * Tai khoan (password chung: `password`):
 *   admin@hoctoan.test
 *   teacher1@hoctoan.test .. teacher2@hoctoan.test
 *   student1@hoctoan.test .. student10@hoctoan.test   (3 trung_binh, 4 kha, 3 gioi)
 *   parent1@hoctoan.test  .. parent5@hoctoan.test     (da link con)
 *
 * student1 co san curriculum + lich su 2 tuan de test dashboard & risk score.
 *
 * Seed la TAT DINH (khong random) de test lap lai duoc ket qua.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AiProviderSeeder::class,
            UserSeeder::class,
            SchoolClassSeeder::class,
            StudentHistorySeeder::class,
            PlanSeeder::class,
        ]);
    }
}
