<?php

namespace App\Services;

use App\Enums\SystemLogAction;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Tạo tài khoản hàng loạt từ bảng dữ liệu (Excel/CSV) gồm các cột: Họ tên, Email, Vai trò.
 */
class UserImportService
{
    public const MAX_ROWS = 500;

    /** Tên cột chấp nhận (đã bỏ dấu, viết thường, bỏ khoảng trắng/ký tự đặc biệt). */
    protected const HEADERS = [
        'name' => ['hoten', 'hovaten', 'ten', 'name', 'fullname'],
        'email' => ['email', 'mail', 'diachiemail', 'thudientu'],
        'role' => ['vaitro', 'role', 'quyen'],
    ];

    /**
     * Kiểm tra từng dòng, trả về danh sách để xem trước.
     *
     * @param  array<int, array<int, string>>  $table  Dòng đầu tiên là tiêu đề cột.
     * @return array<int, array{line: int, name: string, email: string, role: ?string, role_label: string, errors: array<int, string>}>
     */
    public function analyze(array $table): array
    {
        // Bỏ các dòng trống ở đầu file (vẫn đếm để số dòng báo lỗi khớp với số dòng trong Excel)
        $skippedTop = 0;
        while ($table !== [] && static::isEmptyRow(reset($table))) {
            array_shift($table);
            $skippedTop++;
        }

        if ($table === []) {
            throw new BusinessRuleException('File không có dữ liệu.');
        }

        $header = array_shift($table);
        $columns = $this->mapHeader($header);

        $rows = [];
        $seen = [];
        $line = $skippedTop + 1; // số dòng của tiêu đề trong file

        foreach ($table as $raw) {
            $line++;
            if (static::isEmptyRow($raw)) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                throw new BusinessRuleException('Mỗi lần chỉ nhập tối đa '.self::MAX_ROWS.' tài khoản. Hãy chia file thành nhiều phần.');
            }

            $name = preg_replace('/\s+/u', ' ', trim($raw[$columns['name']] ?? ''));
            $email = Str::lower(trim($raw[$columns['email']] ?? ''));
            $roleRaw = isset($columns['role']) ? trim($raw[$columns['role']] ?? '') : '';
            $role = $this->parseRole($roleRaw);

            $errors = [];

            if ($name === '') {
                $errors[] = 'Thiếu họ tên.';
            } elseif (mb_strlen($name) > 255) {
                $errors[] = 'Họ tên quá dài (tối đa 255 ký tự).';
            }

            if ($email === '') {
                $errors[] = 'Thiếu email.';
            } elseif (Validator::make(['email' => $email], ['email' => ['email', 'max:255']])->fails()) {
                $errors[] = 'Email không hợp lệ.';
            } elseif (isset($seen[$email])) {
                $errors[] = "Trùng email với dòng {$seen[$email]} trong file.";
            } elseif (static::emailExists($email)) {
                $errors[] = 'Email này đã có tài khoản trong hệ thống.';
            }

            if ($role === null) {
                $errors[] = "Vai trò \"{$roleRaw}\" không hợp lệ (chỉ nhận: Chuyên viên, Quản lý, Admin).";
            }

            if ($email !== '' && ! isset($seen[$email])) {
                $seen[$email] = $line;
            }

            $rows[] = [
                'line' => $line,
                'name' => $name,
                'email' => $email,
                'role' => $role?->value,
                'role_label' => $role?->label() ?? $roleRaw,
                'errors' => $errors,
            ];
        }

        if ($rows === []) {
            throw new BusinessRuleException('File chỉ có dòng tiêu đề, chưa có dòng dữ liệu nào.');
        }

        return $rows;
    }

    /**
     * Tạo tài khoản cho các dòng hợp lệ (dòng có lỗi bị bỏ qua). Tất cả dùng chung mật khẩu ban đầu.
     *
     * @param  array<int, array{name: string, email: string, role: ?string, errors: array}>  $rows
     * @return array{created: array<int, array{name: string, email: string, role_label: string}>, skipped: array<int, string>}
     */
    public function import(User $actor, array $rows, string $password): array
    {
        if (! $actor->isAdmin()) {
            throw new BusinessRuleException('Chỉ Admin mới được tạo tài khoản.');
        }

        // Mật khẩu ban đầu giống nhau cho mọi tài khoản nên chỉ cần mã hóa 1 lần (nhanh hơn nhiều khi nhập hàng trăm người).
        $hash = Hash::make($password);

        return DB::transaction(function () use ($actor, $rows, $hash) {
            $created = [];
            $skipped = [];

            foreach ($rows as $row) {
                if (! empty($row['errors']) || ! $row['role']) {
                    continue;
                }

                // Kiểm tra lại phòng khi có người vừa tạo tay cùng email trong lúc đang xem trước.
                if (static::emailExists($row['email'])) {
                    $skipped[] = $row['email'];

                    continue;
                }

                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => $hash,
                    'role' => $row['role'],
                    'is_active' => true,
                ]);

                SystemLog::record($actor->id, SystemLogAction::TaoTaiKhoan, "Tạo tài khoản {$user->email} (nhập từ file).", ['user_id' => $user->id]);

                $created[] = ['name' => $user->name, 'email' => $user->email, 'role_label' => $user->role->label()];
            }

            if ($created !== []) {
                SystemLog::record($actor->id, SystemLogAction::NhapTaiKhoan, 'Nhập '.count($created).' tài khoản từ file.', [
                    'emails' => array_column($created, 'email'),
                ]);
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    /** @return array{name: int, email: int, role?: int} */
    protected function mapHeader(array $header): array
    {
        $columns = [];
        foreach ($header as $index => $title) {
            $key = static::normalize((string) $title);
            foreach (self::HEADERS as $field => $aliases) {
                if (! isset($columns[$field]) && in_array($key, $aliases, true)) {
                    $columns[$field] = $index;
                }
            }
        }

        if (! isset($columns['name'], $columns['email'])) {
            throw new BusinessRuleException('Dòng đầu tiên của file phải là tiêu đề cột, có ít nhất 2 cột "Họ tên" và "Email" (cột "Vai trò" không bắt buộc). Hãy dùng file mẫu.');
        }

        return $columns;
    }

    /** Trống -> Chuyên viên. Nhận cả chữ có dấu/không dấu và mã vai trò. */
    protected function parseRole(string $value): ?UserRole
    {
        $key = static::normalize($value);

        return match ($key) {
            '', 'chuyenvien', 'cv' => UserRole::ChuyenVien,
            'quanly', 'ql' => UserRole::QuanLy,
            'admin', 'adminhethong', 'quantrivien', 'quantrihethong' => UserRole::Admin,
            default => null,
        };
    }

    protected static function normalize(string $value): string
    {
        $value = str_replace(['đ', 'Đ'], 'd', $value);

        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($value)));
    }

    protected static function isEmptyRow(array $row): bool
    {
        return trim(implode('', $row)) === '';
    }

    protected static function emailExists(string $email): bool
    {
        return User::whereRaw('LOWER(email) = ?', [Str::lower($email)])->exists();
    }
}
