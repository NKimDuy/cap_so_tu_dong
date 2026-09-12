# Hệ thống Cấp Số Tự Động

Số hóa quy trình đăng ký, cấp phát, theo dõi và lưu trữ số công văn nội bộ của **Trung tâm Đào tạo từ xa** - thay thế quy trình thủ công qua Google Form/Google Sheet trước đây.

Xây dựng bằng **Laravel 13** + **Livewire 3** + **MySQL**, giao diện Tailwind CSS.

## Tính năng chính

- 3 vai trò: **Chuyên viên**, **Quản lý**, **Admin hệ thống** với phân quyền riêng biệt.
- Cấp số công văn **tự động, tuần tự**, reset về 1 vào đầu mỗi năm.
- Xin số **bình thường** (cấp ngay) và **số lùi** (quản lý duyệt, cấp từ kho dự trữ).
- Giới hạn **tối đa 3 số chưa trả** mỗi chuyên viên, có thể cấu hình.
- Quản lý **kho số lùi dự trữ**.
- **Hủy số** thông minh: chuyển thành số lùi dự trữ hoặc thu hồi để cấp lại tùy tình huống.
- **Email tự động**: xác nhận cấp số, theo dõi cho quản lý, yêu cầu duyệt số lùi, nhắc trả số (đúng hạn + lặp mỗi tuần).
- **Báo cáo thống kê**: theo tháng/năm, danh sách tồn đọng, top người xin số nhiều nhất.
- **Nhật ký hệ thống (audit log)** đầy đủ mọi thao tác.

## Cài đặt

Xem hướng dẫn chi tiết tại [`HUONG_DAN_CAI_DAT.md`](./HUONG_DAN_CAI_DAT.md).

Tóm tắt nhanh:

```bash
composer install
copy .env.example .env
php artisan key:generate
# tạo database MySQL tên "cap_so_tu_dong" rồi chạy:
php artisan migrate --seed
php artisan serve
```

## Cấu trúc thư mục chính

```
app/Enums/                  Các enum trạng thái (UserRole, DocumentNumberStatus, ...)
app/Models/                 User, DocumentNumber, BackdateRequest, SystemLog, Setting
app/Services/DocumentNumberService.php   Toàn bộ logic nghiệp vụ cấp số / hủy số / duyệt số lùi
app/Livewire/                Các màn hình (component) theo từng vai trò
app/Mail/                    Các email tự động
app/Console/Commands/        Lệnh gửi email nhắc trả số (chạy theo lịch)
resources/views/livewire/    Giao diện tương ứng từng Livewire component
resources/views/mail/        Mẫu email (Markdown)
database/migrations/         Cấu trúc bảng dữ liệu
database/seeders/            Tài khoản mẫu để chạy thử
```
