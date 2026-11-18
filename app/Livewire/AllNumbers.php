<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasFlashMessages;
use App\Enums\DocumentNumberStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\DocumentNumber;
use App\Models\Signer;
use App\Models\User;
use App\Services\DocumentNumberService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AllNumbers extends Component
{
    use HasFlashMessages;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $requesterFilter = '';
    public string $fromDate = '';
    public string $toDate = '';
    public string $yearFilter = '';

    // Quản lý/Admin sửa thông tin một số
    public ?int $editingId = null;
    public string $editContent = '';
    public string $editSigner = '';
    public string $editExpectedReturnDate = '';

    protected array $messages = [
        'editContent.required' => 'Vui lòng nhập nội dung xin số.',
        'editSigner.required' => 'Vui lòng chọn người ký số.',
        'editSigner.in' => 'Người ký số không có trong danh sách, vui lòng chọn lại.',
        'editExpectedReturnDate.required' => 'Vui lòng chọn ngày dự kiến trả số.',
        'editExpectedReturnDate.date' => 'Ngày dự kiến trả số không hợp lệ.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRequesterFilter(): void
    {
        $this->resetPage();
    }

    public function updatingYearFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
    }

    public function updatingToDate(): void
    {
        $this->resetPage();
    }

    public function complete(int $docId, DocumentNumberService $service): void
    {
        $doc = DocumentNumber::findOrFail($docId);

        try {
            $service->complete(auth()->user(), $doc);
            $this->notifySuccess("Đã xác nhận hoàn thành số {$doc->formatted_number}.");
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function reopen(int $docId, DocumentNumberService $service): void
    {
        $doc = DocumentNumber::findOrFail($docId);

        try {
            $service->reopen(auth()->user(), $doc);
            $this->notifySuccess("Đã mở lại số {$doc->formatted_number}, số trở về trạng thái 'Đã cấp - Chờ trả'.");
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function cancel(int $docId, DocumentNumberService $service): void
    {
        $doc = DocumentNumber::findOrFail($docId);

        try {
            $service->cancel(auth()->user(), $doc, null);
            $this->notifySuccess("Đã hủy số {$doc->formatted_number}.");
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function edit(int $docId): void
    {
        $doc = DocumentNumber::findOrFail($docId);

        if (! in_array($doc->status, [DocumentNumberStatus::DaCap, DocumentNumberStatus::HoanThanh], true)) {
            $this->notifyError("Chỉ sửa được số ở trạng thái 'Đã cấp - Chờ trả' hoặc 'Hoàn thành'.");

            return;
        }

        $this->resetErrorBag();
        $this->editingId = $doc->id;
        $this->editContent = (string) $doc->content;
        $this->editSigner = (string) $doc->signer;
        $this->editExpectedReturnDate = (string) $doc->expected_return_date?->toDateString();
    }

    public function cancelEdit(): void
    {
        $this->resetErrorBag();
        $this->reset(['editingId', 'editContent', 'editSigner', 'editExpectedReturnDate']);
    }

    public function saveEdit(DocumentNumberService $service): void
    {
        if (! $this->editingId) {
            return;
        }

        $doc = DocumentNumber::findOrFail($this->editingId);

        $this->validate([
            'editContent' => ['required', 'string', 'max:255'],
            'editSigner' => ['required', 'string', Rule::in($this->signerOptions($doc))],
            'editExpectedReturnDate' => ['required', 'date'],
        ]);

        try {
            $service->updateInfo(auth()->user(), $doc, [
                'content' => $this->editContent,
                'signer' => $this->editSigner,
                'expected_return_date' => $this->editExpectedReturnDate,
            ]);
            $this->notifySuccess("Đã cập nhật thông tin số {$doc->formatted_number}.");
            $this->cancelEdit();
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    /**
     * Người ký được chọn khi sửa: các người ký đang hiển thị,
     * cộng với người ký hiện tại của số (kể cả khi người đó đã bị ẩn/xóa khỏi danh sách).
     */
    protected function signerOptions(?DocumentNumber $doc): array
    {
        $names = Signer::selectableNames();

        if ($doc?->signer && ! in_array($doc->signer, $names, true)) {
            $names[] = $doc->signer;
        }

        return $names;
    }

    public function render()
    {
        $query = DocumentNumber::query()->with(['requester', 'completedBy', 'registeredBy'])->latest('id');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('content', 'like', "%{$this->search}%")
                    ->orWhere('signer', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->requesterFilter) {
            $query->where('requester_id', $this->requesterFilter);
        }

        if ($this->yearFilter) {
            $query->where('year', (int) $this->yearFilter);
        }

        // Lọc theo NGÀY GHI NHẬN/NGÀY KÝ (cột request_date chứa đúng ngày này cho mọi số đã cấp).
        if ($this->fromDate) {
            $query->whereDate('request_date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('request_date', '<=', $this->toDate);
        }

        return view('livewire.all-numbers', [
            'numbers' => $query->paginate(20),
            'requesters' => User::orderBy('name')->get(),
            'years' => DocumentNumber::query()->select('year')->distinct()->orderByDesc('year')->pluck('year'),
            'editingDoc' => $this->editingId ? DocumentNumber::find($this->editingId) : null,
            'editSignerOptions' => $this->editingId ? $this->signerOptions(DocumentNumber::find($this->editingId)) : [],
        ]);
    }
}
