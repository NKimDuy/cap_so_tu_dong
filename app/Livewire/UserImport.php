<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Livewire\Concerns\HasFlashMessages;
use App\Services\UserImportService;
use App\Support\SpreadsheetReader;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Admin tạo tài khoản hàng loạt từ file Excel (.xlsx) hoặc CSV.
 * Bước 1: tải file + xem trước (báo lỗi từng dòng). Bước 2: nhập mật khẩu chung và xác nhận tạo.
 */
#[Layout('layouts.app')]
class UserImport extends Component
{
    use HasFlashMessages;
    use WithFileUploads;

    public $file;
    public string $password = '';

    /** Kết quả xem trước */
    public array $rows = [];

    /** Kết quả sau khi tạo */
    public array $created = [];
    public array $skipped = [];

    protected array $messages = [
        'file.required' => 'Vui lòng chọn file.',
        'file.extensions' => 'Chỉ nhận file Excel (.xlsx) hoặc CSV (.csv).',
        'file.max' => 'File quá lớn (tối đa 2MB).',
        'password.required' => 'Vui lòng nhập mật khẩu ban đầu.',
        'password.min' => 'Mật khẩu ban đầu tối thiểu 6 ký tự.',
    ];

    public function updatedFile(): void
    {
        // Chọn file khác thì bỏ kết quả xem trước cũ.
        $this->reset(['rows', 'created', 'skipped']);
        $this->resetErrorBag();
        $this->dismissFlash();
    }

    public function preview(UserImportService $service): void
    {
        $this->reset(['rows', 'created', 'skipped']);
        $this->dismissFlash();

        $this->validate(['file' => ['required', 'file', 'max:2048', 'extensions:xlsx,csv,txt']]);

        try {
            $table = SpreadsheetReader::read($this->file->getRealPath(), $this->file->getClientOriginalExtension());
            $this->rows = $service->analyze($table);
        } catch (BusinessRuleException $e) {
            $this->addError('file', $e->getMessage());
        }
    }

    public function import(UserImportService $service): void
    {
        if ($this->rows === []) {
            return;
        }

        $this->validate(['password' => ['required', 'string', 'min:6']]);

        if ($this->validCount() === 0) {
            $this->notifyError('Không có dòng hợp lệ nào để tạo tài khoản.');

            return;
        }

        try {
            $result = $service->import(auth()->user(), $this->rows, $this->password);
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        $this->created = $result['created'];
        $this->skipped = $result['skipped'];
        $this->reset(['rows', 'file', 'password']);

        $this->notifySuccess('Đã tạo '.count($this->created).' tài khoản.');
    }

    public function startOver(): void
    {
        $this->reset(['rows', 'file', 'password', 'created', 'skipped']);
        $this->resetErrorBag();
        $this->dismissFlash();
    }

    public function downloadTemplate()
    {
        return response()->download(
            resource_path('templates/mau_nhap_tai_khoan.xlsx'),
            'mau_nhap_tai_khoan.xlsx'
        );
    }

    public function validCount(): int
    {
        return count(array_filter($this->rows, fn ($r) => empty($r['errors'])));
    }

    public function render()
    {
        return view('livewire.user-import', [
            'validCount' => $this->validCount(),
            'errorCount' => count($this->rows) - $this->validCount(),
        ]);
    }
}
