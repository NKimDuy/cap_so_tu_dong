<?php

namespace App\Enums;

enum UserRole: string
{
    case ChuyenVien = 'chuyen_vien';
    case QuanLy = 'quan_ly';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::ChuyenVien => 'Chuyên viên',
            self::QuanLy => 'Quản lý',
            self::Admin => 'Admin hệ thống',
        };
    }

    public static function options(): array
    {
        return [
            self::ChuyenVien->value => self::ChuyenVien->label(),
            self::QuanLy->value => self::QuanLy->label(),
            self::Admin->value => self::Admin->label(),
        ];
    }
}
