<?php

namespace App\Livewire;

use App\Enums\DocumentNumberStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\DocumentNumber;
use App\Models\Signer;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tự xin số cho chính mình (Chuyên viên, Quản lý, Admin).
 *  - Số bình thường: cấp ngay.
 *  - Số lùi: Chuyên viên gửi yêu cầu chờ duyệt; Quản lý/Admin chọn luôn số dự trữ và được cấp thẳng.
 */
#[Layout('layouts.app')]
class RequestNumber extends Component
{
    public string $mode = 'binh_thuong'; // binh_thuong | so_lui

    public string $content = '';
    public string $signer = '';
    public string $expected_return_date = '';
    public string $requested_date = '';
    public string $pool_id = '';

    /** Quản lý/Admin tự chọn số dự trữ khi xin số lùi (không qua bước chờ duyệt). */
    protected function picksPool(): bool
    {
        return (bool) auth()->user()?->isManagerLike();
    }

    protected function rules(): array
    {
        $rules = [
            'content' => ['required', 'string', 'max:255'],
            // Chỉ được chọn người ký trong danh sách đang hiển thị (do Quản lý/Admin cài đặt).
            'signer' => ['required', 'string', Rule::in(Signer::selectableNames())],
            'expected_return_date' => ['required', 'date', 'after_or_equal:today'],
        ];

        if ($this->mode === 'so_lui') {
            $rules['requested_date'] = ['required', 'date', 'before:today'];
            if ($this->picksPool()) {
                $rules['pool_id'] = ['required'];
            }
        }

        return $rules;
    }

    protected array $messages = [
        'content.required' => 'Vui lòng nhập nội dung xin số.',
        'signer.required' => 'Vui lòng chọn người ký số.',
        'signer.in' => 'Người ký số không có trong danh sách, vui lòng chọn lại.',
        'expected_return_date.required' => 'Vui lòng chọn ngày dự kiến trả số.',
        'expected_return_date.after_or_equal' => 'Ngày dự kiến trả số không được ở trong quá khứ.',
        'requested_date.required' => 'Vui lòng chọn ngày ghi nhận/ngày ký (ngày trong quá khứ).',
        'requested_date.before' => 'Ngày ghi nhận/ngày ký của số lùi phải là một ngày trong quá khứ.',
        'pool_id.required' => 'Vui lòng chọn một số dự trữ trong kho.',
    ];

    public function setMode(string $mode): void
    {
        $this->mode = $mode === 'so_lui' ? 'so_lui' : 'binh_thuong';
        $this->pool_id = '';
        $this->resetErrorBag();
    }

    public function updatedRequestedDate(): void
    {
        // Đổi ngày ký có thể đổi năm -> bỏ số đã chọn để chọn lại trong danh sách mới.
        $this->pool_id = '';
    }

    public function submit(DocumentNumberService $service): void
    {
        $this->validate();

        try {
            if ($this->mode === 'binh_thuong') {
                $doc = $service->issueNormal(auth()->user(), [
                    'content' => $this->content,
                    'signer' => $this->signer,
                    'expected_return_date' => $this->expected_return_date,
                ]);

                session()->flash('success', "Đã cấp số {$doc->formatted_number} thành công.");
            } elseif ($this->picksPool()) {
                $pool = DocumentNumber::findOrFail((int) $this->pool_id);
                $doc = $service->issueBackdateForSelf(auth()->user(), [
                    'content' => $this->content,
                    'signer' => $this->signer,
                    'requested_date' => $this->requested_date,
                    'expected_return_date' => $this->expected_return_date,
                ], $pool);

                session()->flash('success', "Đã cấp số lùi {$doc->formatted_number} thành công.");
            } else {
                $service->requestBackdate(auth()->user(), [
                    'content' => $this->content,
                    'signer' => $this->signer,
                    'requested_date' => $this->requested_date,
                    'expected_return_date' => $this->expected_return_date,
                ]);

                session()->flash('success', 'Đã gửi yêu cầu xin số lùi. Quản lý sẽ xem xét và cấp số từ kho dự trữ.');
            }

            $this->reset(['content', 'signer', 'expected_return_date', 'requested_date', 'pool_id']);

            $this->redirect(route('so.cua-toi'), navigate: false);
        } catch (BusinessRuleException $e) {
            $this->addError('form', $e->getMessage());
        }
    }

    public function render()
    {
        $picksPool = $this->picksPool();

        $signYear = null;
        if ($picksPool && $this->mode === 'so_lui' && $this->requested_date !== '') {
            try {
                $signYear = Carbon::parse($this->requested_date)->year;
            } catch (\Throwable) {
                $signYear = null;
            }
        }

        return view('livewire.request-number', [
            'signers' => Signer::active()->ordered()->get(),
            'picksPool' => $picksPool,
            'signYear' => $signYear,
            'poolNumbers' => $signYear
                ? DocumentNumber::where('status', DocumentNumberStatus::SoLuiDuTru->value)->where('year', $signYear)->orderBy('number')->get()
                : collect(),
        ]);
    }
}
