<div class="max-w-2xl space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Xin số công văn</h1>
        <p class="text-sm text-slate-500">Điền thông tin bên dưới để xin số công văn bình thường hoặc số lùi.</p>
    </div>

    <div class="flex gap-2">
        <button type="button" wire:click="setMode('binh_thuong')"
                class="px-4 py-2 text-sm font-medium rounded-lg {{ $mode === 'binh_thuong' ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-600' }}">
            Xin số bình thường
        </button>
        <button type="button" wire:click="setMode('so_lui')"
                class="px-4 py-2 text-sm font-medium rounded-lg {{ $mode === 'so_lui' ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-600' }}">
            Xin số lùi
        </button>
    </div>

    <x-card>
        @error('form')
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">{{ $message }}</div>
        @enderror

        <form wire:submit="submit" class="space-y-4">
            <div>
                <x-input-label for="content" required>Nội dung xin số</x-input-label>
                <x-text-input wire:model="content" id="content" placeholder="VD: Quyết định khen thưởng sinh viên..." />
                <x-error for="content" />
            </div>

            <div>
                <x-input-label for="signer" required>Người ký số</x-input-label>
                <x-text-input wire:model="signer" id="signer" placeholder="VD: TS. Nguyễn Kim Phước" />
                <x-error for="signer" />
            </div>

            @if($mode === 'so_lui')
                <div>
                    <x-input-label for="requested_date" required>Ngày xin số (ngày quá khứ)</x-input-label>
                    <x-text-input type="date" wire:model="requested_date" id="requested_date" />
                    <x-error for="requested_date" />
                    <p class="text-xs text-slate-500 mt-1">Yêu cầu sẽ được gửi cho quản lý xem xét và cấp số từ kho dự trữ.</p>
                </div>
            @endif

            <div>
                <x-input-label for="expected_return_date" required>Ngày dự kiến trả số</x-input-label>
                <x-text-input type="date" wire:model="expected_return_date" id="expected_return_date" />
                <x-error for="expected_return_date" />
            </div>

            <div class="pt-2">
                <x-primary-button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">
                        {{ $mode === 'binh_thuong' ? 'Xin số ngay' : 'Gửi yêu cầu xin số lùi' }}
                    </span>
                    <span wire:loading wire:target="submit">Đang xử lý...</span>
                </x-primary-button>
            </div>
        </form>
    </x-card>
</div>
