{{-- Thông báo của component Livewire (xem App\Livewire\Concerns\HasFlashMessages) --}}
@if ($flashSuccess)
    <div class="flex items-start justify-between gap-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
        <span>{{ $flashSuccess }}</span>
        <button type="button" wire:click="dismissFlash" class="text-green-700 hover:text-green-900 leading-none" aria-label="Đóng">&times;</button>
    </div>
@endif
@if ($flashError)
    <div class="flex items-start justify-between gap-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
        <span>{{ $flashError }}</span>
        <button type="button" wire:click="dismissFlash" class="text-red-700 hover:text-red-900 leading-none" aria-label="Đóng">&times;</button>
    </div>
@endif
