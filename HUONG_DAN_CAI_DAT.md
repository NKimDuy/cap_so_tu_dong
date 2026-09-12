# Hướng dẫn cài đặt - Hệ thống Cấp Số Tự Động

Hệ thống được xây dựng bằng **Laravel 13 + Livewire 3 + MySQL**, giao diện dùng Tailwind CSS (tải qua CDN, **không cần cài Node/npm**). Bạn chỉ cần PHP + Composer + MySQL để chạy toàn bộ hệ thống.

## 1. Yêu cầu môi trường

- PHP >= 8.3 (khuyến nghị dùng **Laragon** hoặc **XAMPP** trên Windows, đã có sẵn PHP + MySQL + Apache)
- Composer (đi kèm Laragon, hoặc tải tại https://getcomposer.org)
- MySQL 5.7+ / MariaDB (đi kèm Laragon/XAMPP)

Kiểm tra nhanh bằng lệnh (mở Terminal/Cmder của Laragon, hoặc PowerShell/CMD):

```
php -v
composer -V
```

## 2. Cài đặt lần đầu

Mở terminal tại thư mục dự án `D:\cap_so_tu_dong` rồi chạy lần lượt:

```bash
# 1. Cài các thư viện PHP (Laravel, Livewire...) - cần có mạng Internet
composer install

# 2. Tạo file cấu hình .env từ file mẫu
copy .env.example .env

# 3. Tạo khóa mã hóa ứng dụng
php artisan key:generate
```

### Tạo cơ sở dữ liệu MySQL

Mở phpMyAdmin (Laragon: click "Database") hoặc HeidiSQL, tạo database mới tên **`cap_so_tu_dong`**, charset **`utf8mb4`**.

Hoặc dùng lệnh:

```bash
mysql -u root -e "CREATE DATABASE cap_so_tu_dong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Mở file `.env`, kiểm tra đúng thông tin kết nối (mặc định Laragon dùng user `root`, mật khẩu rỗng - thường không cần sửa gì):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cap_so_tu_dong
DB_USERNAME=root
DB_PASSWORD=
```

### Khởi tạo bảng dữ liệu + tài khoản mẫu

```bash
php artisan migrate --seed
```

Lệnh này tạo toàn bộ bảng và **tài khoản mẫu để đăng nhập thử ngay**:

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Admin | admin@ttdtx.edu.vn | password123 |
| Quản lý | phuoc.nk@ttdtx.edu.vn | password123 |
| Chuyên viên | nhung.vth@ttdtx.edu.vn | password123 |
| Chuyên viên | sang.pdp@ttdtx.edu.vn | password123 |
| Chuyên viên | chau.tq@ttdtx.edu.vn | password123 |

> ⚠️ Đây là dữ liệu mẫu để chạy thử. Vào **Admin → Tài khoản người dùng** để tạo tài khoản thật, đổi mật khẩu, khóa/xóa các tài khoản mẫu trước khi đưa vào sử dụng chính thức.

### Chạy thử ứng dụng

```bash
php artisan serve
```

Mở trình duyệt: **http://127.0.0.1:8000**

Nếu dùng Laragon, bạn cũng có thể tạo virtual host để chạy qua tên miền `.test` (Laragon → phải chuột thư mục → Quick app → hoặc tự trỏ Apache vào thư mục `public/` của dự án) thay vì dùng `php artisan serve`.

## 3. Cấu hình gửi email thật

Mặc định `.env` có `MAIL_MAILER=log` — email sẽ **không gửi thật**, chỉ được ghi vào file `storage/logs/laravel.log` để bạn xem thử nội dung. Khi sẵn sàng dùng thật, sửa `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ten-tai-khoan@gmail.com
MAIL_PASSWORD=mat-khau-ung-dung-16-ky-tu
MAIL_FROM_ADDRESS="ten-tai-khoan@gmail.com"
MAIL_FROM_NAME="Cấp Số Tự Động - TT.ĐTTX"
```

Nếu dùng Gmail: vào **Google Account → Bảo mật → Xác minh 2 bước** (bật lên), sau đó tạo **Mật khẩu ứng dụng (App Password)** và dán vào `MAIL_PASSWORD` (không dùng mật khẩu Gmail thường). Nếu trung tâm có email server riêng (Office 365, email trường...), dùng thông tin SMTP của server đó thay vào.

Sau khi sửa `.env`, chạy `php artisan config:clear` rồi thử lại.

## 4. Thiết lập lịch tự động nhắc trả số (bắt buộc để tính năng nhắc nhở hoạt động)

Hệ thống có lệnh `php artisan so:nhac-tra-so` chạy hàng ngày lúc 08:00 để gửi email nhắc chuyên viên trả số quá hạn (và lặp lại mỗi 7 ngày). Laravel cần một tiến trình gọi `php artisan schedule:run` **mỗi phút** để kiểm tra và tự kích hoạt việc này.

### Cách 1 - Windows Task Scheduler (khuyến nghị cho máy chủ chạy 24/7)

1. Mở **Task Scheduler** → **Create Task**.
2. Tab **General**: đặt tên `Cap So Tu Dong - Scheduler`, chọn "Run whether user is logged on or not".
3. Tab **Triggers** → **New** → chọn "Daily", "Repeat task every: 1 minute", "for a duration of: Indefinitely".
4. Tab **Actions** → **New**:
   - Program/script: đường dẫn tới `php.exe` (VD: `C:\laragon\bin\php\php-8.3.x\php.exe`)
   - Add arguments: `artisan schedule:run`
   - Start in: `D:\cap_so_tu_dong`
5. Lưu lại. Từ giờ mỗi phút Windows sẽ tự chạy kiểm tra lịch.

### Cách 2 - Đơn giản hơn (chạy thủ công / máy dùng ít giờ)

Mở một cửa sổ terminal riêng tại `D:\cap_so_tu_dong` và để chạy nền:

```bash
php artisan schedule:work
```

Cửa sổ này cần luôn mở thì lịch nhắc mới hoạt động. Phù hợp khi test hoặc chạy trên máy cá nhân.

## 5. Đăng nhập và bắt đầu sử dụng

- Chuyên viên: xin số tại menu **"Xin số công văn"**, xem lịch sử tại **"Số của tôi"**.
- Quản lý: duyệt số lùi tại **"Duyệt số lùi"**, quản lý kho tại **"Kho số lùi dự trữ"**, xác nhận hoàn thành/hủy số tại **"Tất cả số công văn"**, xem báo cáo tại **"Báo cáo thống kê"**.
- Admin: tạo tài khoản tại **"Tài khoản người dùng"**, chỉnh giới hạn 3 số / chu kỳ nhắc tại **"Cấu hình hệ thống"**, tra cứu **"Nhật ký hệ thống"**.

## 6. Một số quyết định thiết kế cần lưu ý

- **Năm cấp số** tự động lấy theo năm dương lịch hiện tại của máy chủ (tự động reset về số 1 vào 01/01 hàng năm đúng theo quy tắc trong kế hoạch) — không cần cấu hình thủ công.
- Email được gửi **đồng bộ ngay khi thao tác** (không cần chạy thêm `php artisan queue:work`). Nếu sau này lượng người dùng lớn và muốn gửi email nền, có thể thêm `ShouldQueue` vào các class trong `app/Mail/` và chạy queue worker.
- Chỉ Admin mới tạo được tài khoản (không có trang đăng ký công khai), đúng theo mô hình "tài khoản do Admin cấp".
- Giao diện dùng Tailwind CDN nên máy chạy ứng dụng (trình duyệt người dùng) cần có Internet để tải CSS. Nếu trung tâm cần chạy hoàn toàn offline, có thể cài Node.js rồi chạy `npm install && npm run build` và chuyển layout sang dùng CSS đã build sẵn (liên hệ để được hỗ trợ thêm).

## 7. Khắc phục sự cố thường gặp

| Lỗi | Nguyên nhân / cách xử lý |
| --- | --- |
| `SQLSTATE[HY000] [1049] Unknown database` | Chưa tạo database `cap_so_tu_dong` trong MySQL, xem lại mục 2. |
| `could not find driver` khi migrate | PHP thiếu extension `pdo_mysql`, bật trong `php.ini` (Laragon thường đã bật sẵn) hoặc đổi bản PHP trong Laragon. |
| Trang trắng / lỗi 500 | Chạy `php artisan config:clear && php artisan cache:clear` rồi thử lại; xem chi tiết lỗi trong `storage/logs/laravel.log`. |
| Không nhận được email | Kiểm tra lại `.env` mục MAIL_*, hoặc tạm thời để `MAIL_MAILER=log` rồi mở `storage/logs/laravel.log` xem nội dung email đã "gửi". |
| Không thấy nhắc nhở trả số | Kiểm tra đã thiết lập Task Scheduler / `schedule:work` theo mục 4 chưa. |

---
Cần hỗ trợ thêm, quay lại trò chuyện này với Claude và mô tả lỗi gặp phải (kèm nội dung trong `storage/logs/laravel.log` nếu có).
