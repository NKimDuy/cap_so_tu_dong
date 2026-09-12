<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Tất cả số công văn</h1>
        <p class="text-sm text-slate-500">Theo dõi toàn bộ số công văn, xác nhận hoàn thành hoặc hủy số.</p>
    </div>

    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <x-input-label>Tìm kiếm</x-input-label>
                <x-text-input wire:model.live.debounce.400ms="search" placeholder="Nội dung / người ký..." />
            </div>
            <div>
                <x-input-label>Trạng thái</x-input-label>
                <select wire:model.live="statusFilter" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Tất cả</option>
                    <option value="da_cap">Đã cấp - Chờ trả</option>
                    <option value="hoan_thanh">Hoàn thành</option>
                    <option value="so_lui_du_tru">Số lùi dự trữ</option>
                </select>
            </div>
            <div>
                <x-input-label>Người xin</x-input-label>
                <select wire:model.live="requesterFilter" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($requesters as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label>Từ ngày - Đến ngày</x-input-label>
                <div class="flex gap-2">
                    <x-text-input type="date" wire:model.live="fromDate" />
                    <x-text-input type="date" wire:model.live="toDate" />
                </div>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Số</th>
                        <th class="py-2 pr-4">Người xin</th>
                        <th class="py-2 pr-4">Nội dung</th>
                        <th class="py-2 pr-4">Loại</th>
                        <th class="py-2 pr-4">Ngày ghi nhận</th>
                        <th class="py-2 pr-4">Hạn trả</th>
                        <th class="py-2 pr-4">Trạng thái</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($numbers as $doc)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 font-medium">{{ $doc->formatted_number }}</td>
                            <td class="py-2 pr-4">{{ $doc->requester?->name ?? '-' }}</td>
                            <td class="py-2 pr-4">{{ $doc->content ?? '-' }}</td>
                            <td class="py-2 pr-4">{{ $doc->is_backdate ? 'Số lùi' : 'Bình thường' }}</td>
                            <td class="py-2 pr-4">{{ $doc->record_date?->format('d/m/Y') }}</td>
                            <td class="py-2 pr-4 {{ $doc->status->value === 'da_cap' && $doc->expected_return_date?->isPast() ? 'text-red-600 font-medium' : '' }}">
                                {{ $doc->expected_return_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="py-2 pr-4"><x-status-badge :status="$doc->status" /></td>
                            <td class="py-2 pr-4 text-right whitespace-nowrap">
                                @if($doc->status->value === 'da_cap')
                                    <button wire:click="complete({{ $doc->id }})"
                                            wire:confirm="Xác nhận chuyên viên đã trả số {{ $doc->formatted_number }}?"
                                            class="text-green-600 hover:underline text-xs mr-2">Hoàn thành</button>
                                    <button wire:click="cancel({{ $doc->id }})"
                                            wire:confirm="Bạn có chắc muốn hủy số {{ $doc->formatted_number }}?"
                                            class="text-red-600 hover:underline text-xs">Hủy số</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-slate-400">Không có dữ liệu phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $numbers->links() }}</div>
    </x-card>
</div>
