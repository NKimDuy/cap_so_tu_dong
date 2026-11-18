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
                <select wire:model="signer" id="signer"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                    <option value="">-- Chọn người ký số --</option>
                    @foreach($signers as $s)
                        <option value="{{ $s->name }}">{{ $s->name }}</option>
                    @endforeach
                    <option value="{{ \App\Models\Signer::OTHER }}">Khác (chưa xác định, bổ sung sau)</option>
                </select>
                <x-error for="signer" />
                @if($signers->isEmpty())
                    <p class="text-xs text-amber-700 mt-1">Chưa có người ký số nào trong danh sách. Vui lòng liên hệ Quản lý để cài đặt.</p>
                @endif
            </div>

            @if($mode === 'so_lui' && $picksPool)
                {{-- Quản lý/Admin: chọn luôn số dự trữ, được cấp thẳng --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="requested_date" required>Ngày ghi nhận/Ngày ký (quá khứ)</x-input-label>
                        <x-text-input type="date" wire:model.live="requested_date" id="requested_date" />
                        <x-error for="requested_date" />
                    </div>
                    <div>
                        <x-input-label for="pool_id" required>Số dự trữ</x-input-label>
                        @if(! $signYear)
                            <p class="text-sm text-slate-500 py-2">Chọn ngày ký trước để hiện số dự trữ cùng năm.</p>
                        @elseif($poolNumbers->isEmpty())
                            <p class="text-sm text-amber-700 py-2">Kho không còn số dự trữ năm {{ $signYear }}.</p>
                        @else
                            <select wire:model="pool_id" id="pool_id"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                                <option value="">-- Chọn số dự trữ năm {{ $signYear }} --</option>
                                @foreach($poolNumbers as $p)
                                    <option value="{{ $p->id }}">{{ $p->formatted_number }}</option>
                                @endforeach
                            </select>
                        @endif
                        <x-error for="pool_id" />
                    </div>
                </div>
                <p class="text-xs text-slate-500 -mt-2">Bạn là Quản lý/Admin nên số lùi được cấp thẳng từ kho, không qua bước chờ duyệt.</p>
            @elseif($mode === 'so_lui')
                <div>
                    <x-input-label for="requested_date" required>Ngày ghi nhận/Ngày ký (ngày trong quá khứ)</x-input-label>
                    <x-text-input type="date" wire:model="requested_date" id="requested_date" />
                    <x-error for="requested_date" />
                    <p class="text-xs text-slate-500 mt-1">Yêu cầu sẽ được gửi cho quản lý xem xét và cấp một số dự trữ <strong>cùng năm</strong> với ngày này. Nếu kho không còn số của năm đó, quản lý sẽ từ chối.</p>
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
                        {{ $mode === 'binh_thuong' ? 'Xin số ngay' : ($picksPool ? 'Cấp số lùi' : 'Gửi yêu cầu xin số lùi') }}
                    </span>
                    <span wire:loading wire:target="submit">Đang xử lý...</span>
                </x-primary-button>
            </div>
        </form>
    </x-card>
</div>
