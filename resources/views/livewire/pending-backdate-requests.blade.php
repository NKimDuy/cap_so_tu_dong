<div class="space-y-6">
    @include('partials.flash')

    <div>
        <h1 class="text-xl font-semibold text-slate-800">Duyệt yêu cầu xin số lùi</h1>
        <p class="text-sm text-slate-500">Chọn một số lùi dự trữ trong kho để cấp cho từng yêu cầu, hoặc từ chối nếu không hợp lệ.</p>
    </div>

    @if($poolByYear->isEmpty())
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
            Kho số lùi dự trữ hiện đang trống. Hãy vào mục "Kho số lùi dự trữ" để giữ thêm số trước khi duyệt.
        </div>
    @endif

    @forelse($requests as $req)
        <x-card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1 text-sm">
                    <div class="font-semibold text-slate-800">{{ $req->requester?->name }} <span class="text-slate-400 font-normal">({{ $req->requester?->email }})</span></div>
                    <div><span class="text-slate-500">Nội dung:</span> {{ $req->content }}</div>
                    <div><span class="text-slate-500">Người ký số:</span> {{ $req->signer }}</div>
                    <div><span class="text-slate-500">Ngày ghi nhận/Ngày ký:</span> <span class="font-medium">{{ $req->requested_date->format('d/m/Y') }}</span></div>
                    <div><span class="text-slate-500">Ngày dự kiến trả:</span> {{ $req->expected_return_date->format('d/m/Y') }}</div>
                    <div class="text-xs text-slate-400">Gửi lúc {{ $req->created_at->format('d/m/Y H:i') }}</div>
                </div>

                @php($signYear = $req->requested_date->year)
                @php($yearPool = $poolByYear->get($signYear, collect()))
                <div class="w-full sm:w-72 space-y-2">
                    @if($yearPool->isNotEmpty())
                        <select wire:model="selectedPool.{{ $req->id }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">-- Chọn số dự trữ năm {{ $signYear }} --</option>
                            @foreach($yearPool as $pool)
                                <option value="{{ $pool->id }}">{{ $pool->formatted_number }}</option>
                            @endforeach
                        </select>
                        <button wire:click="approve({{ $req->id }})"
                                wire:confirm="Xác nhận cấp số này cho {{ $req->requester?->name }}?"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700">
                            Duyệt và cấp số
                        </button>
                    @else
                        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs px-3 py-2">
                            Không có số dự trữ năm {{ $signYear }} trong kho nên không thể cấp. Hãy từ chối yêu cầu này (ghi lý do bên dưới).
                            @if($signYear === now()->year)
                                Hoặc giữ thêm số ở mục "Kho số lùi dự trữ" rồi quay lại duyệt.
                            @endif
                        </div>
                    @endif

                    <input type="text" wire:model="rejectReason.{{ $req->id }}" placeholder="Lý do từ chối (không bắt buộc)"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                    <button wire:click="reject({{ $req->id }})"
                            wire:confirm="Xác nhận từ chối yêu cầu này?"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-red-300 text-red-600 text-xs font-medium rounded-lg hover:bg-red-50">
                        Từ chối
                    </button>
                </div>
            </div>
        </x-card>
    @empty
        <x-card>
            <p class="text-sm text-slate-500 text-center py-4">Không có yêu cầu xin số lùi nào đang chờ duyệt.</p>
        </x-card>
    @endforelse
</div>
