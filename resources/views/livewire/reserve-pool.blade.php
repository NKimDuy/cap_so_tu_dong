<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Kho số lùi dự trữ</h1>
        <p class="text-sm text-slate-500">Số lùi dự trữ không tính vào giới hạn 3 số của chuyên viên và không phát sinh nhắc nhở, chỉ được cấp khi có yêu cầu xin số lùi.</p>
    </div>

    <x-card title="Giữ số lùi dự trữ mới">
        <form wire:submit="reserve" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[220px]">
                <x-input-label for="notes">Ghi chú (không bắt buộc)</x-input-label>
                <x-text-input wire:model="notes" id="notes" placeholder="VD: giữ dự phòng cho quý 3..." />
            </div>
            <x-primary-button type="submit">Giữ số tiếp theo</x-primary-button>
        </form>
    </x-card>

    <x-card title="Danh sách số lùi dự trữ hiện có ({{ $pool->count() }})">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Số</th>
                        <th class="py-2 pr-4">Người giữ</th>
                        <th class="py-2 pr-4">Ngày giữ</th>
                        <th class="py-2 pr-4">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pool as $doc)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 font-medium">{{ $doc->formatted_number }}</td>
                            <td class="py-2 pr-4">{{ $doc->heldBy?->name ?? '-' }}</td>
                            <td class="py-2 pr-4">{{ $doc->record_date?->format('d/m/Y') }}</td>
                            <td class="py-2 pr-4 text-slate-500">{{ $doc->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-400">Kho số lùi dự trữ đang trống.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
