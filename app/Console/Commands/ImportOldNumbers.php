<?php

namespace App\Console\Commands;

use App\Enums\DocumentNumberStatus;
use App\Enums\SystemLogAction;
use App\Exceptions\BusinessRuleException;
use App\Models\DocumentNumber;
use App\Models\Signer;
use App\Models\SystemLog;
use App\Models\User;
use App\Support\SpreadsheetReader;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Nhập các số đã cấp trên Google Form (file Excel xuất ra) vào hệ thống, theo khoảng ngày cấp số.
 * Dùng một lần khi chuyển đổi, ví dụ:
 *   php artisan he-thong:nhap-so-cu "D:\du_lieu\xin_so.xlsx" --nam=2026 --tu-ngay=01/11/2026 --thu
 *
 * - Người xin được khớp với tài khoản theo HỌ TÊN (không phân biệt hoa thường, dấu, và bỏ "Cô/Thầy/Anh/Chị").
 * - Có lỗi ở bất kỳ dòng nào thì KHÔNG nhập gì cả.
 * - Không ghi đè số đã có trong hệ thống.
 */
class ImportOldNumbers extends Command
{
    protected $signature = 'he-thong:nhap-so-cu
        {file : Đường dẫn file Excel (.xlsx) xuất từ Google Form}
        {--nam= : Năm cần nhập, trùng tên trang tính trong file (mặc định: năm hiện tại)}
        {--tu-ngay= : Chỉ nhập các số có "Ngày cấp số văn bản" từ ngày này, dạng dd/mm/yyyy (bắt buộc)}
        {--thu : Chạy thử: chỉ in kết quả kiểm tra, không ghi vào database}';

    protected $description = 'Nhập các số đã cấp trên Google Form (file Excel) vào hệ thống, theo khoảng ngày cấp số';

    /** Tiêu đề cột trong file Google Form (đã bỏ dấu, viết thường, bỏ khoảng trắng) -> tên trường. */
    protected const COLUMNS = [
        'number' => ['sovanban'],
        'issued' => ['ngaycapso'],
        'backdate' => ['solui'],
        'returned' => ['daguilai'],
        'timestamp' => ['dauthoigian'],
        'name' => ['hovaten', 'nguoidangky'],
        'content' => ['tomtat', 'noidung'],
        'signer' => ['lanhdaoky', 'nguoiky'],
        'due' => ['dukien'],
        'note' => ['ghichu'],
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        $year = (int) ($this->option('nam') ?: now()->year);
        $from = SpreadsheetReader::toDate((string) $this->option('tu-ngay'));

        if (! $from) {
            $this->error('Thiếu hoặc sai --tu-ngay (dạng dd/mm/yyyy), ví dụ: --tu-ngay=01/11/2026');

            return self::FAILURE;
        }
        if (! is_file($file)) {
            $this->error("Không tìm thấy file: {$file}");

            return self::FAILURE;
        }

        try {
            $table = SpreadsheetReader::read($file, 'xlsx', (string) $year);
            $cols = $this->mapColumns($table[0] ?? []);
        } catch (BusinessRuleException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        [$rows, $errors, $warnings] = $this->analyze(array_slice($table, 1), $cols, $year, $from);

        if ($rows === [] && $errors === []) {
            $this->warn('Không có số nào có ngày cấp từ '.$from->format('d/m/Y').' trong trang tính '.$year.'.');

            return self::SUCCESS;
        }

        $this->table(
            ['Dòng', 'Số', 'Ngày ký', 'Người xin', 'Nội dung', 'Người ký', 'Hạn trả', 'Trạng thái'],
            array_map(fn ($r) => [
                $r['line'],
                sprintf('%02d/%d', $r['number'], $year),
                $r['signed']?->format($r['is_backdate'] ? 'd/m/Y' : 'd/m/Y H:i') ?? '-',
                $r['user']?->name ?? '-',
                Str::limit($r['content'], 40),
                $r['signer'] ?? '-',
                $r['due']?->format('d/m/Y') ?? '-',
                $r['status_label'],
            ], $rows)
        );

        foreach ($warnings as $w) {
            $this->warn('Lưu ý: '.$w);
        }

        if ($errors) {
            foreach ($errors as $e) {
                $this->error('Lỗi: '.$e);
            }
            $this->error('Có lỗi nên KHÔNG nhập số nào. Hãy sửa rồi chạy lại.');

            return self::FAILURE;
        }

        if ($this->option('thu')) {
            $this->info('Chạy thử: dữ liệu hợp lệ, '.count($rows).' số sẽ được nhập. Chưa ghi gì vào database.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Nhập '.count($rows).' số trên vào hệ thống?', false)) {
            $this->info('Đã hủy, không nhập gì.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $year) {
            foreach ($rows as $r) {
                if (DocumentNumber::where('year', $year)->where('number', $r['number'])->lockForUpdate()->exists()) {
                    throw new BusinessRuleException(sprintf('Số %02d/%d vừa được cấp trong lúc nhập.', $r['number'], $year));
                }

                DocumentNumber::create($r['attributes']);
            }

            SystemLog::record(null, SystemLogAction::NhapSoCu, 'Nhập '.count($rows).' số đã cấp từ dữ liệu cũ (Google Form): '
                .implode(', ', array_map(fn ($r) => sprintf('%02d/%d', $r['number'], $year), $rows)).'.');
        });

        $next = (int) DocumentNumber::where('year', $year)->max('number') + 1;
        $this->info('Đã nhập '.count($rows).' số. Số tiếp theo hệ thống sẽ cấp cho năm '.$year.': '.sprintf('%02d/%d', $next, $year).'.');

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    protected function mapColumns(array $header): array
    {
        $cols = [];
        foreach ($header as $i => $title) {
            $key = self::normalize((string) $title);
            foreach (self::COLUMNS as $field => $needles) {
                if (isset($cols[$field])) {
                    continue;
                }
                foreach ($needles as $needle) {
                    if ($field === 'number' ? str_starts_with($key, $needle) : str_contains($key, $needle)) {
                        $cols[$field] = $i;
                        break;
                    }
                }
            }
        }

        foreach (['number', 'issued', 'name', 'content'] as $required) {
            if (! isset($cols[$required])) {
                throw new BusinessRuleException('File không đúng mẫu Google Form: thiếu cột "'.match ($required) {
                    'number' => 'Số văn bản', 'issued' => 'Ngày Cấp số văn bản', 'name' => 'Họ và tên người đăng ký', 'content' => 'Tóm tắt nội dung',
                }.'".');
            }
        }

        return $cols;
    }

    /** @return array{0: array, 1: array<int, string>, 2: array<int, string>} */
    protected function analyze(array $data, array $cols, int $year, Carbon $from): array
    {
        $users = User::all();
        $signers = Signer::pluck('name')->all();
        $rows = [];
        $errors = [];
        $warnings = [];
        $seen = [];

        foreach ($data as $idx => $raw) {
            $line = $idx + 2;
            $get = fn (string $f) => isset($cols[$f]) ? trim((string) ($raw[$cols[$f]] ?? '')) : '';

            if (trim(implode('', $raw)) === '') {
                continue;
            }

            $issued = SpreadsheetReader::toDate($get('issued'));
            if (! $issued || $issued->copy()->startOfDay()->lt($from)) {
                continue;
            }

            // Excel lưu số dạng "464" hoặc "464.0"; số phụ như "143A" thì chưa hỗ trợ.
            $numText = preg_replace('/^(\d+)\.0+$/', '$1', $get('number'));
            if (! preg_match('/^\d+$/', $numText)) {
                $errors[] = "Dòng {$line}: số \"{$numText}\" không phải số nguyên (hệ thống chưa hỗ trợ số phụ).";

                continue;
            }
            $number = (int) $numText;
            $label = sprintf('%02d/%d', $number, $year);

            if (isset($seen[$number])) {
                $errors[] = "Dòng {$line}: số {$label} bị trùng với dòng {$seen[$number]} trong file.";

                continue;
            }
            $seen[$number] = $line;

            if (DocumentNumber::where('year', $year)->where('number', $number)->exists()) {
                $errors[] = "Dòng {$line}: số {$label} đã có trong hệ thống.";

                continue;
            }

            $content = $get('content');
            $name = $get('name');
            $note = $get('note') ?: null;
            $timestamp = SpreadsheetReader::toDate($get('timestamp'));
            // Ngày cấp kèm giờ thật (lấy giờ từ "Dấu thời gian" nếu cùng ngày)
            $issuedAt = ($timestamp && $timestamp->isSameDay($issued)) ? $timestamp : $issued;

            $isReserve = Str::contains(Str::lower($content), 'dự phòng')
                || ($name === '' && Str::contains(Str::lower($content), 'chờ nội dung'));

            if ($isReserve) {
                $rows[] = [
                    'line' => $line, 'number' => $number, 'is_backdate' => true, 'signed' => null, 'user' => null,
                    'content' => $content, 'signer' => null, 'due' => null, 'status_label' => 'Số lùi dự trữ',
                    'attributes' => [
                        'year' => $year, 'number' => $number, 'status' => DocumentNumberStatus::SoLuiDuTru->value,
                        'is_backdate' => true, 'record_date' => $issuedAt,
                        'notes' => trim('Nhập từ dữ liệu cũ: '.$content.($note ? '. '.$note : '')),
                    ],
                ];

                continue;
            }

            $user = $this->matchUser($users, $name, $line, $errors);
            if (! $user) {
                continue;
            }

            $backdate = $get('backdate') !== '' ? SpreadsheetReader::toDate($get('backdate')) : null;
            if ($get('backdate') !== '' && ! $backdate) {
                $errors[] = "Dòng {$line}: ngày đăng ký số lùi \"{$get('backdate')}\" không đúng định dạng ngày.";

                continue;
            }

            $due = $get('due') !== '' ? SpreadsheetReader::toDate($get('due')) : null;
            if ($get('due') !== '' && ! $due) {
                $warnings[] = "Dòng {$line} (số {$label}): ngày dự kiến gửi lại \"{$get('due')}\" sai định dạng, nhập với hạn trả để trống.";
            }

            $returnedText = $get('returned');
            $returned = $returnedText !== '' ? SpreadsheetReader::toDate($returnedText) : null;
            $completed = $returnedText !== '';
            if ($completed && ! $returned) {
                $warnings[] = "Dòng {$line} (số {$label}): cột đã gửi lại ghi \"{$returnedText}\" (không phải ngày), nhập là Hoàn thành không có ngày.";
            }

            $signer = $this->matchSigner($signers, $get('signer'));
            if ($signer !== Signer::OTHER && ! in_array($signer, $signers, true)) {
                $warnings[] = "Dòng {$line} (số {$label}): người ký \"{$signer}\" chưa có trong danh sách Người ký số, vẫn nhập nguyên tên.";
            }
            if (self::normalize($name) !== self::normalize($user->name)) {
                $warnings[] = "Dòng {$line} (số {$label}): tên \"{$name}\" được gán cho tài khoản \"{$user->name}\".";
            }

            $rows[] = [
                'line' => $line, 'number' => $number, 'is_backdate' => (bool) $backdate,
                'signed' => $backdate ?: $issuedAt, 'user' => $user, 'content' => $content, 'signer' => $signer, 'due' => $due,
                'status_label' => $completed ? 'Hoàn thành'.($returned ? ' ('.$returned->format('d/m/Y').')' : '') : 'Đã cấp - Chờ trả',
                'attributes' => [
                    'year' => $year,
                    'number' => $number,
                    'status' => ($completed ? DocumentNumberStatus::HoanThanh : DocumentNumberStatus::DaCap)->value,
                    'is_backdate' => (bool) $backdate,
                    'requester_id' => $user->id,
                    'content' => Str::limit($content, 255, ''),
                    'signer' => $signer,
                    'request_date' => ($backdate ?: $issued)->toDateString(),
                    'record_date' => $issuedAt,
                    'expected_return_date' => $due?->toDateString(),
                    'completed_at' => $completed ? ($returned ?? $issuedAt) : null,
                    'notes' => trim('Nhập từ dữ liệu cũ (Google Form).'.($note ? ' '.$note : '')),
                ],
            ];
        }

        return [$rows, $errors, $warnings];
    }

    protected function matchUser($users, string $name, int $line, array &$errors): ?User
    {
        if ($name === '') {
            $errors[] = "Dòng {$line}: thiếu họ tên người xin.";

            return null;
        }

        $key = self::normalizeName($name);
        $found = $users->filter(fn (User $u) => self::normalizeName($u->name) === $key);

        if ($found->count() === 1) {
            return $found->first();
        }
        if ($found->count() > 1) {
            $errors[] = "Dòng {$line}: có nhiều tài khoản trùng tên \"{$name}\" (".$found->pluck('email')->implode(', ').').';

            return null;
        }

        $errors[] = "Dòng {$line}: chưa có tài khoản cho \"{$name}\". Hãy tạo tài khoản (đúng họ tên) rồi chạy lại.";

        return null;
    }

    protected function matchSigner(array $signers, string $value): string
    {
        $key = self::normalize($value);
        if (in_array($key, ['', 'detrong', 'khongco', 'khac'], true)) {
            return Signer::OTHER;
        }
        foreach ($signers as $s) {
            if (self::normalize($s) === $key) {
                return $s;
            }
        }

        return $value;
    }

    /** Bỏ dấu, viết thường, bỏ khoảng trắng/ký tự đặc biệt. */
    protected static function normalize(string $value): string
    {
        $value = str_replace(['đ', 'Đ'], 'd', $value);

        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($value)));
    }

    /** Như normalize nhưng bỏ danh xưng ở đầu (Cô, Thầy, Anh, Chị, Ông, Bà). */
    protected static function normalizeName(string $name): string
    {
        $name = preg_replace('/^\s*(cô|thầy|anh|chị|ông|bà)\s+/iu', '', trim($name));

        return self::normalize($name);
    }
}
