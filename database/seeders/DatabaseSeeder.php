<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Setting::set('max_pending_numbers', 3);
        Setting::set('reminder_interval_days', 7);

        $password = 'password123';

        User::updateOrCreate(
            ['email' => 'admin@ttdtx.edu.vn'],
            [
                'name' => 'Admin Hệ Thống',
                'password' => bcrypt($password),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'phuoc.nk@ttdtx.edu.vn'],
            [
                'name' => 'Nguyễn Kim Phước',
                'password' => bcrypt($password),
                'role' => 'quan_ly',
                'is_active' => true,
            ]
        );

        $chuyenVien = [
            ['name' => 'Võ Thị Hồng Nhung', 'email' => 'nhung.vth@ttdtx.edu.vn'],
            ['name' => 'Phạm Dương Phước Sang', 'email' => 'sang.pdp@ttdtx.edu.vn'],
            ['name' => 'Trần Quỳnh Châu', 'email' => 'chau.tq@ttdtx.edu.vn'],
        ];

        foreach ($chuyenVien as $cv) {
            User::updateOrCreate(
                ['email' => $cv['email']],
                [
                    'name' => $cv['name'],
                    'password' => bcrypt($password),
                    'role' => 'chuyen_vien',
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Đã tạo tài khoản mẫu. Mật khẩu cho tất cả tài khoản: '.$password);
    }
}
