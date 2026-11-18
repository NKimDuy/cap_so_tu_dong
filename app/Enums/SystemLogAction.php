<?php

namespace App\Enums;

enum SystemLogAction: string
{
    case XinSoBinhThuong = 'xin_so_binh_thuong';
    case XinSoLui = 'xin_so_lui';
    case XinSoHo = 'xin_so_ho';
    case DuyetCapSoLui = 'duyet_cap_so_lui';
    case TuChoiSoLui = 'tu_choi_so_lui';
    case GiuSoLuiDuTru = 'giu_so_lui_du_tru';
    case NhapSoDuTruCu = 'nhap_so_du_tru_cu';
    case NhapSoCu = 'nhap_so_cu';
    case HoanThanhTraSo = 'hoan_thanh_tra_so';
    case MoLaiSo = 'mo_lai_so';
    case HuySoChuyenDuTru = 'huy_so_chuyen_du_tru';
    case HuySoThuHoi = 'huy_so_thu_hoi';
    case SuaThongTinSo = 'sua_thong_tin_so';
    case ThemNguoiKy = 'them_nguoi_ky';
    case SuaNguoiKy = 'sua_nguoi_ky';
    case XoaNguoiKy = 'xoa_nguoi_ky';
    case NhacTraSo = 'nhac_tra_so';
    case TaoTaiKhoan = 'tao_tai_khoan';
    case SuaTaiKhoan = 'sua_tai_khoan';
    case KhoaTaiKhoan = 'khoa_tai_khoan';
    case MoKhoaTaiKhoan = 'mo_khoa_tai_khoan';
    case DoiMatKhau = 'doi_mat_khau';
    case NhapTaiKhoan = 'nhap_tai_khoan';
    case XoaDuLieuThu = 'xoa_du_lieu_thu';
    case CapNhatCauHinh = 'cap_nhat_cau_hinh';
    case DangNhap = 'dang_nhap';
    case DangXuat = 'dang_xuat';

    public function label(): string
    {
        return match ($this) {
            self::XinSoBinhThuong => 'Xin số bình thường',
            self::XinSoLui => 'Xin số lùi',
            self::XinSoHo => 'Xin số giúp chuyên viên',
            self::DuyetCapSoLui => 'Duyệt cấp số lùi',
            self::TuChoiSoLui => 'Từ chối yêu cầu số lùi',
            self::GiuSoLuiDuTru => 'Giữ số lùi dự trữ',
            self::NhapSoDuTruCu => 'Nhập số dự trữ cũ vào kho',
            self::NhapSoCu => 'Nhập số đã cấp từ dữ liệu cũ',
            self::HoanThanhTraSo => 'Xác nhận hoàn thành (trả số)',
            self::MoLaiSo => 'Mở lại số (bỏ xác nhận hoàn thành)',
            self::HuySoChuyenDuTru => 'Hủy số - chuyển thành số lùi dự trữ',
            self::HuySoThuHoi => 'Hủy số - thu hồi số',
            self::SuaThongTinSo => 'Sửa thông tin số công văn',
            self::ThemNguoiKy => 'Thêm người ký số',
            self::SuaNguoiKy => 'Sửa người ký số',
            self::XoaNguoiKy => 'Xóa người ký số',
            self::NhacTraSo => 'Gửi email nhắc trả số',
            self::TaoTaiKhoan => 'Tạo tài khoản',
            self::SuaTaiKhoan => 'Sửa tài khoản',
            self::KhoaTaiKhoan => 'Khóa tài khoản',
            self::MoKhoaTaiKhoan => 'Mở khóa tài khoản',
            self::DoiMatKhau => 'Tự đổi mật khẩu',
            self::NhapTaiKhoan => 'Nhập tài khoản từ file',
            self::XoaDuLieuThu => 'Xóa dữ liệu chạy thử',
            self::CapNhatCauHinh => 'Cập nhật cấu hình hệ thống',
            self::DangNhap => 'Đăng nhập',
            self::DangXuat => 'Đăng xuất',
        };
    }
}
