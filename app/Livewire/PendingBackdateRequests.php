<?php

namespace App\Livewire;

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
    public array $selectedPool = [];
    public array $rejectReason = [];

    public function approve(int $requestId, DocumentNumberService $service): void
    {
        $req = BackdateRequest::findOrFail($requestId);
        $poolId = $this->selectedPool[$requestId] ?? null;

        if (! $poolId) {
            session()->flash('error', 'Vui lòng chọn một số lùi dự trữ trong kho trước khi duyệt.');

            return;
        }

        $pool = DocumentNumber::findOrFail($poolId);

        try {
            $doc = $service->approveBackdateRequest(auth()->user(), $req, $pool);
            session()->flash('success', "Đã cấp số {$doc->formatted_number} cho {$req->requester->name}.");
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject(int $requestId, DocumentNumberService $service): void
    {
        $req = BackdateRequest::findOrFail($requestId);

        try {
            $service->rejectBackdateRequest(auth()->user(), $req, $this->rejectReason[$requestId] ?? null);
            session()->flash('success', 'Đã từ chối yêu cầu xin số lùi.');
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.pending-backdate-requests', [
            'requests' => BackdateRequest::with('requester')
                ->where('status', BackdateRequestStatus::DangCho->value)
                ->oldest()
                ->get(),
            'poolNumbers' => DocumentNumber::where('status', DocumentNumberStatus::SoLuiDuTru->value)
                ->orderBy('number')
                ->get(),
        ]);
    }
}
