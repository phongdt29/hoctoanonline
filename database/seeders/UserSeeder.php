<?php

namespace Database\Seeders;

use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Ticket F4 — 1 admin, 2 teacher, 10 student (3 trung_binh / 4 kha / 3 gioi), 5 parent.
 *
 * Luu y ve `self_assessed_level` vs `math_gpa`: mot so hoc sinh duoc co tinh tao
 * LECH giua tu khai va nang luc that (vd student5 tu khai `gioi`, gpa 8.5 nhung
 * se lam test sai nhieu). Day la du lieu de chung minh phan loai tang 2 lat nguoc
 * duoc tang 1 — DoD ticket C4.
 */
class UserSeeder extends Seeder
{
    /** [email, ten, khoi, tu khai, gpa] — TAT DINH, khong random. */
    private const STUDENTS = [
        // 3 trung_binh
        ['student1',  'Nguyen Van An',    7, 'trung_binh', 4.5],
        ['student2',  'Tran Thi Binh',    6, 'trung_binh', 5.0],
        ['student3',  'Le Van Cuong',     8, 'trung_binh', 4.8],
        // 4 kha
        ['student4',  'Pham Thi Dung',    7, 'kha',        6.5],
        ['student5',  'Hoang Van Em',     9, 'kha',        7.2],
        ['student6',  'Vu Thi Giang',    10, 'kha',        7.8],
        ['student7',  'Dang Van Hung',    6, 'kha',        6.9],
        // 3 gioi
        ['student8',  'Bui Thi Lan',     11, 'gioi',       8.5],
        ['student9',  'Do Van Minh',     12, 'gioi',       9.0],
        ['student10', 'Ngo Thi Nga',      8, 'gioi',       8.8],
    ];

    /** [email, ten, quan he, cac student index duoc link] */
    private const PARENTS = [
        ['parent1', 'Nguyen Van Cha',  'bo',            [1]],
        ['parent2', 'Tran Thi Me',     'me',            [2, 3]],   // 1 phu huynh 2 con
        ['parent3', 'Le Thi Hoa',      'me',            [4]],
        ['parent4', 'Pham Van Tuan',   'bo',            [5, 6]],
        ['parent5', 'Vo Thi Kim',      'nguoi_giam_ho', [7]],
    ];

    public function run(): void
    {
        User::create([
            'name'     => 'Quan tri vien',
            'email'    => 'admin@hoctoan.test',
            'password' => 'password',
            'role'     => User::ROLE_ADMIN,
        ]);

        // Tai khoan admin theo yeu cau (dang nhap that).
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@gmail.com',
            'password' => 'admin@',
            'role'     => User::ROLE_ADMIN,
        ]);

        foreach ([1, 2] as $i) {
            User::create([
                'name'     => "Giao vien {$i}",
                'email'    => "teacher{$i}@hoctoan.test",
                'password' => 'password',
                'role'     => User::ROLE_TEACHER,
            ]);
        }

        $students = [];

        foreach (self::STUDENTS as $index => [$slug, $name, $grade, $level, $gpa]) {
            $user = User::create([
                'name'     => $name,
                'email'    => "{$slug}@hoctoan.test",
                'password' => 'password',
                'role'     => User::ROLE_STUDENT,
            ]);

            $students[$index + 1] = Student::create([
                'user_id'             => $user->id,
                'full_name'           => $name,
                'date_of_birth'       => now()->subYears(18 - $grade + 6)->toDateString(),
                'address'             => 'Ha Noi',
                'phone'               => '09'.str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
                'school_name'         => 'THCS/THPT Demo',
                'grade'               => $grade,
                'self_assessed_level' => $level,
                'math_gpa'            => $gpa,
                'tutor_gender'        => $index % 2 === 0 ? 'co' : 'thay',
                'favorite_color'      => ['#4f46e5', '#0ea5e9', '#10b981'][$index % 3],
                'interests'           => ['bong da', 'game', 'am nhac'],
                'status'              => Student::STATUS_REGISTERED,
                'invite_code'         => 'HT'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
            ]);
        }

        foreach (self::PARENTS as [$slug, $name, $relation, $childIndexes]) {
            $user = User::create([
                'name'     => $name,
                'email'    => "{$slug}@hoctoan.test",
                'password' => 'password',
                'role'     => User::ROLE_PARENT,
            ]);

            $parent = ParentAccount::create([
                'user_id'             => $user->id,
                'full_name'           => $name,
                'phone'               => '098'.str_pad((string) count($childIndexes), 7, '0', STR_PAD_LEFT),
                'relation_to_student' => $relation,
            ]);

            foreach ($childIndexes as $i) {
                $parent->children()->attach($students[$i]->id, ['linked_via' => 'invite_code']);
            }
        }
    }
}
