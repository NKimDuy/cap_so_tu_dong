<?php

namespace App\Livewire;

use App\Enums\DocumentNumberStatus;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Concerns\HasFlashMessages;
use App\Models\DocumentNumber;
use App\Models\Signer;
use App\Models\User;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Quản lý/Admin xin số giúp người khác (chuyên viên, quản lý hoặc admin đang hoạt động).
 *  - Số bình thường: cấp ngay như người đó tự xin.
 *  - Số lùi: chọn luôn một số dự trữ cùng năm với ngày ký và cấp ngay (không qua bước chờ duyệt).
 * Giới hạn số chưa trả vẫn áp dụng nếu người được xin giúp là chuyên viên; Quản lý/Admin không giới hạn.
 */
#[Layout('layouts.app')]
class RequestOnBehalf extends Component
{
    use HasFlashMessages;

    public string $mode = 'binh_thuong'; // binh_thuong | so_lui

    public string $requester_id = '';
    public string $content = '';
    public string $signer = '';
    public string $expected_return_date = '';
    public string $requested_date = '';
    public string $pool_id = '';

    protected function rules(): array
    {
        $rules = [
            'requester_id' => ['required', Rule::exists('users', 'id')->where('is_active', true)],
            'content' => ['required', 'string', 'max:255'],
            'signer' => ['required', 'string', Rule::in(Signer::selectableNames())],
            'expected_return_date' => ['required', 'date', 'after_or_equal:today'],
        ];

        if ($this->mode === 'so_lui') {
            $rules['requested_date'] = ['required', 'date', 'before:today'];
            $rules['pool_id'] = ['required'];
        }

        return $rules;
    }

    protected array $messages = [
        'requester_id.required' => 'Vui lòng chọn người cần xin số giúp.',
        'requester_id.exists' => 'Người được chọn không hợp lệ hoặc tài khoản đã bị khóa.',
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

        $requester = User::findOrFail((int) $this->requester_id);
        $data = [
            'content' => $this->content,
            'signer' => $this->signer,
            'expected_return_date' => $this->expected_return_date,
        ];

        try {
            if ($this->mode === 'binh_thuong') {
                $doc = $service->issueNormal($requester, $data, auth()->user());
            } else {
                $pool = DocumentNumber::findOrFail((int) $this->pool_id);
                $doc = $service->issueBackdateOnBehalf(auth()->user(), $requester, $data + ['requested_date' => $this->requested_date], $pool);
            }
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        $this->reset(['content', 'signer', 'expected_return_date', 'requested_date', 'pool_id']);
        $this->resetErrorBag();
        $this->notifySuccess("Đã cấp số {$doc->formatted_number} cho {$requester->name}.");
    }

    public function render(DocumentNumberService $service)
    {
        $selected = $this->requester_id !== '' ? User::find((int) $this->requester_id) : null;

        $signYear = null;
        if ($this->mode === 'so_lui' && $this->requested_date !== '') {
            try {
                $signYear = Carbon::parse($this->requested_date)->year;
            } catch (\Throwable) {
                $signYear = null;
            }
        }

        return view('livewire.request-on-behalf', [
            'specialists' => User::active()->orderBy('name')->get(),
            'signers' => Signer::active()->ordered()->get(),
            'selected' => $selected,
            'outstanding' => $selected ? $service->countOutstanding($selected) : 0,
            'selectedHasLimit' => $selected ? $service->hasPendingLimit($selected) : true,
            'maxPending' => $service->maxPending(),
            'signYear' => $signYear,
            'poolNumbers' => $signYear
                ? DocumentNumber::where('status', DocumentNumberStatus::SoLuiDuTru->value)->where('year', $signYear)->orderBy('number')->get()
                : collect(),
        ]);
    }
}
