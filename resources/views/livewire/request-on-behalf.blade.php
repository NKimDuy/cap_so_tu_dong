<div class="max-w-2xl space-y-6">
    @include('partials.flash')

    <div>
        <h1 class="text-xl font-semibold text-slate-800">Xin số giúp người khác</h1>
        <p class="text-sm text-slate-500">Dùng khi người cần số không tự xin được. Số được cấp đứng tên người đó và ghi nhận người đăng ký hộ.</p>
    </div>

    <div class="flex gap-2">
        <button type="button" wire:click="setMode('binh_thuong')"
                class="px-4 py-2 text-sm font-medium rounded-lg {{ $mode === 'binh_thuong' ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-600' }}">
            Số bình thường
        </button>
        <button type="button" wire:click="setMode('so_lui')"
                class="px-4 py-2 text-sm font-medium rounded-lg {{ $mode === 'so_lui' ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-600' }}">
            Số lùi
        </button>
    </div>

    <x-card>
        <form wire:submit="submit" class="space-y-4">
            <div>
                <x-input-label for="requester_id" required>Xin số cho</x-input-label>
                <select wire:model.live="requester_id" id="requester_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                    <option value="">-- Chọn người cần xin số --</option>
                    @foreach($specialists as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }}){{ $u->isManagerLike() ? ' - '.$u->role->label() : '' }}</option>
                    @endforeach
                </select>
                <x-error for="requester_id" />
                @if($selected && ! $selectedHasLimit)
                    <p class="text-xs mt-1 text-slate-500">Đang có {{ $outstanding }} số chưa trả (Quản lý/Admin không giới hạn).</p>
                @elseif($selected)
                    <p class="text-xs mt-1 {{ $outstanding >= $maxPending ? 'text-red-600 font-medium' : 'text-slate-500' }}">
                        Đang có {{ $outstanding }}/{{ $maxPending }} số chưa trả.
                        @if($outstanding >= $maxPending) Đã đủ giới hạn, cần xác nhận hoàn thành bớt trước khi xin thêm. @endif
                    </p>
                @endif
            </div>

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
            </div>

            @if($mode === 'so_lui')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="requested_date" required>Ngày ghi nhận/Ngày ký (quá khứ)</x-input-label>
                        <x-text-input type="date" wire:model.live="requested_date" id="requested_date" />
                        <x-error for="requested_date" />
                    </div>
                    <div>
                        <x-input-label for="pool_id" required>Số dự trữ cấp cho</x-input-label>
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
            @endif

            <div>
                <x-input-label for="expected_return_date" required>Ngày dự kiến trả số</x-input-label>
                <x-text-input type="date" wire:model="expected_return_date" id="expected_return_date" />
                <x-error for="expected_return_date" />
            </div>

            <div class="pt-2">
                <x-primary-button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">{{ $mode === 'binh_thuong' ? 'Cấp số' : 'Cấp số lùi' }}</span>
                    <span wire:loading wire:target="submit">Đang xử lý...</span>
                </x-primary-button>
            </div>
        </form>
    </x-card>
</div>
