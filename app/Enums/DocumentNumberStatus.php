<?php

namespace App\Enums;

enum DocumentNumberStatus: string
{
    case DaCap = 'da_cap';               // Đã cấp - Chờ trả
    case HoanThanh = 'hoan_thanh';        // Hoàn thành
    case SoLuiDuTru = 'so_lui_du_tru';    // Số lùi dự trữ (còn trong kho, chưa gán cho ai)

    public function label(): string
    {
        return match ($this) {
            self::DaCap => 'Đã cấp - Chờ trả',
            self::HoanThanh => 'Hoàn thành',
            self::SoLuiDuTru => 'Số lùi dự trữ',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DaCap => 'amber',
            self::HoanThanh => 'green',
            self::SoLuiDuTru => 'slate',
        };
    }
}
