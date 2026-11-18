<?php

namespace App\Livewire;

use App\Enums\DocumentNumberStatus;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Concerns\HasFlashMessages;
use App\Models\DocumentNumber;
use App\Services\DocumentNumberService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReservePool extends Component
{
    use HasFlashMessages;

    public string $notes = '';

    // Nhập số dự trữ cũ (đã giữ trước khi dùng hệ thống)
    public string $oldYear = '';
    public string $oldNumbers = '';
    public string $oldNotes = '';

    public function mount(): void
    {
        $this->oldYear = (string) now()->year;
    }

    public function reserve(DocumentNumberService $service): void
    {
        $doc = $service->reserveBackdateNumber(auth()->user(), $this->notes ?: null);
        $this->notes = '';

        $this->notifySuccess("Đã giữ số {$doc->formatted_number} vào kho số lùi dự trữ.");
    }

    /**
     * Nhập một hoặc nhiều số dự trữ cũ cùng năm, ví dụ: "188, 192, 194".
     * Mỗi số được kiểm tra riêng; số nào lỗi thì báo lỗi, các số hợp lệ vẫn được nhập.
     */
    public function addOld(DocumentNumberService $service): void
    {
        $this->validate([
            'oldYear' => ['required', 'integer', 'min:2000', 'max:'.now()->year],
            'oldNumbers' => ['required', 'string', 'regex:/^[\d\s,;]+$/'],
        ], [
            'oldYear.required' => 'Vui lòng nhập năm.',
            'oldYear.integer' => 'Năm không hợp lệ.',
            'oldYear.min' => 'Năm không hợp lệ.',
            'oldYear.max' => 'Không nhập được số của năm sau.',
            'oldNumbers.required' => 'Vui lòng nhập số.',
            'oldNumbers.regex' => 'Chỉ nhập chữ số, các số cách nhau bằng dấu phẩy. Ví dụ: 188, 192, 194',
        ]);

        $numbers = array_values(array_unique(array_map('intval', preg_split('/[\s,;]+/', trim($this->oldNumbers), -1, PREG_SPLIT_NO_EMPTY))));
        sort($numbers);

        $added = [];
        $errors = [];
        foreach ($numbers as $n) {
            try {
                $added[] = $service->addExistingPoolNumber(auth()->user(), (int) $this->oldYear, $n, $this->oldNotes ?: null)->formatted_number;
            } catch (BusinessRuleException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($added) {
            $this->oldNumbers = '';
            $this->oldNotes = '';
        }

        $message = $added ? 'Đã nhập vào kho: '.implode(', ', $added).'.' : '';
        if ($errors) {
            $this->notifyError(trim($message.' Không nhập được: '.implode(' ', $errors)));
        } else {
            $this->notifySuccess($message);
        }
    }

    public function render()
    {
        return view('livewire.reserve-pool', [
            'pool' => DocumentNumber::where('status', DocumentNumberStatus::SoLuiDuTru->value)
                ->with('heldBy')
                ->orderByDesc('year')
                ->orderBy('number')
                ->get(),
        ]);
    }
}
