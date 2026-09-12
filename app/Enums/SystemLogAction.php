<?php

namespace App\Enums;

enum SystemLogAction: string
{
    case XinSoBinhThuong = 'xin_so_binh_thuong';
    case XinSoLui = 'xin_so_lui';
    case DuyetCapSoLui = 'duyet_cap_so_lui';
    case TuChoiSoLui = 'tu_choi_so_lui';
    case GiuSoLuiDuTru = 'giu_so_lui_du_tru';
    case HoanThanhTraSo = 'hoan_thanh_tra_so';
    case HuySoChuyenDuTru = 'huy_so_chuyen_du_tru';
    case HuySoThuHoi = 'huy_so_thu_hoi';
    case NhacTraSo = 'nhac_tra_so';
    case TaoTaiKhoan = 'tao_tai_khoan';
    case SuaTaiKhoan = 'sua_tai_khoan';
    case KhoaTaiKhoan = 'khoa_tai_khoan';
    case MoKhoaTaiKhoan = 'mo_khoa_tai_khoan';
    case CapNhatCauHinh = 'cap_nhat_cau_hinh';
    case DangNhap = 'dang_nhap';
    case DangXuat = 'dang_xuat';

    public function label(): string
    {
        return match ($this) {
            self::XinSoBinhThuong => 'Xin số bình thường',
            self::XinSoLui => 'Xin số lùi',
            self::DuyetCapSoLui => 'Duyệt cấp số lùi',
            self::TuChoiSoLui => 'Từ chối yêu cầu số lùi',
            self::GiuSoLuiDuTru => 'Giữ số lùi dự trữ',
            self::HoanThanhTraSo => 'Xác nhận hoàn thành (trả số)',
            self::HuySoChuyenDuTru => 'Hủy số - chuyển thành số lùi dự trữ',
            self::HuySoThuHoi => 'Hủy số - thu hồi số',
            self::NhacTraSo => 'Gửi email nhắc trả số',
            self::TaoTaiKhoan => 'Tạo tài khoản',
            self::SuaTaiKhoan => 'Sửa tài khoản',
            self::KhoaTaiKhoan => 'Khóa tài khoản',
            self::MoKhoaTaiKhoan => 'Mở khóa tài khoản',
            self::CapNhatCauHinh => 'Cập nhật cấu hình hệ thống',
            self::DangNhap => 'Đăng nhập',
            self::DangXuat => 'Đăng xuất',
        };
    }
}
