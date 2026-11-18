<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasFlashMessages;
use App\Enums\BackdateRequestStatus;
use App\Enums\DocumentNumberStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\BackdateRequest;
use App\Models\DocumentNumber;
use App\Services\DocumentNumberService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PendingBackdateRequests extends Component
{
    use HasFlashMessages;

    public array $selectedPool = [];
    public array $rejectReason = [];

    public function approve(int $requestId, DocumentNumberService $service): void
    {
        $req = BackdateRequest::findOrFail($requestId);
        $poolId = $this->selectedPool[$requestId] ?? null;

        if (! $poolId) {
            $this->notifyError('Vui lòng chọn một số lùi dự trữ trong kho trước khi duyệt.');

            return;
        }

        $pool = DocumentNumber::findOrFail($poolId);

        try {
            $doc = $service->approveBackdateRequest(auth()->user(), $req, $pool);
            $this->notifySuccess("Đã cấp số {$doc->formatted_number} cho {$req->requester->name}.");
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function reject(int $requestId, DocumentNumberService $service): void
    {
        $req = BackdateRequest::findOrFail($requestId);

        try {
            $service->rejectBackdateRequest(auth()->user(), $req, $this->rejectReason[$requestId] ?? null);
            $this->notifySuccess('Đã từ chối yêu cầu xin số lùi.');
        } catch (BusinessRuleException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.pending-backdate-requests', [
            'requests' => BackdateRequest::with('requester')
                ->where('status', BackdateRequestStatus::DangCho->value)
                ->oldest()
                ->get(),
            // Nhóm số dự trữ theo năm: mỗi yêu cầu chỉ được chọn số cùng năm với ngày ghi nhận/ngày ký.
            'poolByYear' => DocumentNumber::where('status', DocumentNumberStatus::SoLuiDuTru->value)
                ->orderBy('year')
                ->orderBy('number')
                ->get()
                ->groupBy('year'),
        ]);
    }
}
