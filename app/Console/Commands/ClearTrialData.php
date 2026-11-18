<?php

namespace App\Console\Commands;

use App\Enums\SystemLogAction;
use App\Enums\UserRole;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Xóa dữ liệu chạy thử để bắt đầu dùng chính thức:
 *  - Toàn bộ số công văn, yêu cầu số lùi, nhật ký hệ thống.
 *  - 5 tài khoản mẫu tạo bởi "php artisan migrate --seed".
 * GIỮ NGUYÊN: các tài khoản khác (admin mới, tài khoản nhập từ file...), danh sách người ký số, cấu hình hệ thống.
 * Sau khi xóa, số công văn bắt đầu lại từ 01.
 */
class ClearTrialData extends Command
{
    protected $signature = 'he-thong:xoa-du-lieu-thu {--force : Không hỏi xác nhận}';

    protected $description = 'Xóa dữ liệu chạy thử (số công văn, yêu cầu số lùi, nhật ký) và các tài khoản mẫu';

    /** Các tài khoản mẫu do DatabaseSeeder tạo ra. */
    public const DEMO_EMAILS = [
        'admin@ttdtx.edu.vn',
        'phuoc.nk@ttdtx.edu.vn',
        'nhung.vth@ttdtx.edu.vn',
        'sang.pdp@ttdtx.edu.vn',
        'chau.tq@ttdtx.edu.vn',
    ];

    public function handle(): int
    {
        $demoUsers = User::whereIn('email', self::DEMO_EMAILS)->orderBy('email')->get();

        $otherAdmins = User::where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->whereNotIn('email', self::DEMO_EMAILS)
            ->count();

        if ($otherAdmins === 0) {
            $this->error('Chưa có tài khoản Admin nào khác ngoài tài khoản mẫu admin@ttdtx.edu.vn.');
            $this->line('Hãy đăng nhập Admin, vào "Tài khoản người dùng" tạo một tài khoản Admin mới (email thật), rồi chạy lại lệnh này.');
            $this->line('Nếu xóa ngay bây giờ, hệ thống sẽ không còn ai đăng nhập được với quyền Admin.');

            return self::FAILURE;
        }

        $this->warn('SẼ XÓA VĨNH VIỄN các dữ liệu sau (hãy sao lưu database trước):');
        $this->table(['Dữ liệu', 'Số lượng'], [
            ['Số công văn', DB::table('document_numbers')->count()],
            ['Yêu cầu xin số lùi', DB::table('backdate_requests')->count()],
            ['Dòng nhật ký hệ thống', DB::table('system_logs')->count()],
            ['Tài khoản mẫu', $demoUsers->count().($demoUsers->isNotEmpty() ? ' ('.$demoUsers->pluck('email')->implode(', ').')' : '')],
        ]);
        $this->line('Giữ nguyên: các tài khoản khác, danh sách người ký số, cấu hình hệ thống.');

        if (! $this->option('force') && ! $this->confirm('Bạn chắc chắn muốn xóa?', false)) {
            $this->info('Đã hủy, không xóa gì.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($demoUsers) {
            // Hai bảng tham chiếu lẫn nhau: gỡ liên kết trước rồi mới xóa.
            DB::table('document_numbers')->update(['backdate_request_id' => null]);
            DB::table('backdate_requests')->delete();
            DB::table('document_numbers')->delete();
            DB::table('system_logs')->delete();

            if ($demoUsers->isNotEmpty()) {
                DB::table('sessions')->whereIn('user_id', $demoUsers->pluck('id'))->delete();
                User::whereIn('id', $demoUsers->pluck('id'))->delete();
            }
        });

        SystemLog::record(null, SystemLogAction::XoaDuLieuThu, 'Đã xóa dữ liệu chạy thử và tài khoản mẫu để bắt đầu dùng chính thức.');

        $this->info('Đã xóa xong. Số công văn tiếp theo sẽ bắt đầu từ 01.');
        $this->line('Các tài khoản còn lại trong hệ thống (hãy kiểm tra, tài khoản thử nào không dùng thì khóa trong mục "Tài khoản người dùng"):');
        $this->table(['Họ tên', 'Email', 'Vai trò', 'Trạng thái'], User::orderBy('name')->get()->map(fn (User $u) => [
            $u->name, $u->email, $u->role->label(), $u->is_active ? 'Hoạt động' : 'Đã khóa',
        ])->all());

        return self::SUCCESS;
    }
}
