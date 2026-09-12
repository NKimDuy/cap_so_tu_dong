<?php

namespace App\Enums;

enum BackdateRequestStatus: string
{
    case DangCho = 'dang_cho';    // Đang chờ quản lý duyệt
    case DaDuyet = 'da_duyet';    // Đã duyệt, đã gán số
    case TuChoi = 'tu_choi';      // Bị từ chối

    public function label(): string
    {
        return match ($this) {
            self::DangCho => 'Đang chờ duyệt',
            self::DaDuyet => 'Đã duyệt - Đã cấp số',
            self::TuChoi => 'Từ chối',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DangCho => 'amber',
            self::DaDuyet => 'green',
            self::TuChoi => 'red',
        };
    }
}
