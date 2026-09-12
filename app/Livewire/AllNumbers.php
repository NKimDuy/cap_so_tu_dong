<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\DocumentNumber;
use App\Models\User;
use App\Services\DocumentNumberService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AllNumbers extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $requesterFilter = '';
    public string $fromDate = '';
    public string $toDate = '';

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

    public function complete(int $docId, DocumentNumberService $service): void
    {
        $doc = DocumentNumber::findOrFail($docId);

        try {
            $service->complete(auth()->user(), $doc);
            session()->flash('success', "Đã xác nhận hoàn thành số {$doc->formatted_number}.");
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancel(int $docId, DocumentNumberService $service): void
    {
        $doc = DocumentNumber::findOrFail($docId);

        try {
            $service->cancel(auth()->user(), $doc, null);
            session()->flash('success', "Đã hủy số {$doc->formatted_number}.");
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = DocumentNumber::query()->with('requester')->latest('id');

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

        if ($this->fromDate) {
            $query->whereDate('record_date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('record_date', '<=', $this->toDate);
        }

        return view('livewire.all-numbers', [
            'numbers' => $query->paginate(20),
            'requesters' => User::where('role', 'chuyen_vien')->orderBy('name')->get(),
        ]);
    }
}
