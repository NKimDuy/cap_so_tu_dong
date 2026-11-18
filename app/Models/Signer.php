<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Người ký số: danh sách để chuyên viên chọn khi xin số.
 * Khi cấp số, TÊN người ký được lưu thẳng vào số công văn (cột signer),
 * nên đổi tên / ẩn / xóa người ký sau này không làm thay đổi các số đã cấp.
 */
class Signer extends Model
{
    /**
     * Lựa chọn "Khác": dùng khi chưa xác định người ký (hoặc văn bản không cần người ký).
     * Quản lý có thể sửa lại tên người ký sau bằng nút "Sửa" ở mục Tất cả số công văn.
     */
    public const OTHER = 'Khác';

    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Tên các người ký đang hiển thị, theo thứ tự sắp xếp. */
    public static function activeNames(): array
    {
        return static::active()->ordered()->pluck('name')->all();
    }

    /** Các giá trị được chọn trong ô "Người ký số": người ký đang hiển thị + "Khác". */
    public static function selectableNames(): array
    {
        return [...static::activeNames(), self::OTHER];
    }
}
