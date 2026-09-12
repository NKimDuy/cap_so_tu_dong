<?php

namespace App\Livewire;

use App\Enums\DocumentNumberStatus;
use App\Models\DocumentNumber;
use App\Services\DocumentNumberService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReservePool extends Component
{
    public string $notes = '';

    public function reserve(DocumentNumberService $service): void
    {
        $doc = $service->reserveBackdateNumber(auth()->user(), $this->notes ?: null);
        $this->notes = '';

        session()->flash('success', "Đã giữ số {$doc->formatted_number} vào kho số lùi dự trữ.");
    }

    public function render()
    {
        return view('livewire.reserve-pool', [
            'pool' => DocumentNumber::where('status', DocumentNumberStatus::SoLuiDuTru->value)
                ->with('heldBy')
                ->orderBy('number')
                ->get(),
        ]);
    }
}
