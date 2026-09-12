<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\DocumentNumber;
use App\Services\DocumentNumberService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MyNumbers extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
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
        $query = DocumentNumber::where('requester_id', auth()->id())->latest('id');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.my-numbers', [
            'numbers' => $query->paginate(15),
        ]);
    }
}
