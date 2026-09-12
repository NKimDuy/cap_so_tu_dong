<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Số của tôi</h1>
            <p class="text-sm text-slate-500">Lịch sử các số công văn bạn đã xin và trạng thái hiện tại.</p>
        </div>
        <a href="{{ route('so.xin-so') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700">+ Xin số mới</a>
    </div>

    <div class="flex gap-2 items-center">
        <label class="text-sm text-slate-600">Lọc trạng thái:</label>
        <select wire:model.live="statusFilter" class="rounded-lg border-slate-300 text-sm">
            <option value="">Tất cả</option>
            <option value="da_cap">Đã cấp - Chờ trả</option>
            <option value="hoan_thanh">Hoàn thành</option>
        </select>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Số</th>
                        <th class="py-2 pr-4">Loại</th>
                        <th class="py-2 pr-4">Nội dung</th>
                        <th class="py-2 pr-4">Người ký</th>
                        <th class="py-2 pr-4">Ngày xin</th>
                        <th class="py-2 pr-4">Hạn trả</th>
                        <th class="py-2 pr-4">Trạng thái</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($numbers as $doc)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 font-medium">{{ $doc->formatted_number }}</td>
                            <td class="py-2 pr-4">{{ $doc->is_backdate ? 'Số lùi' : 'Bình thường' }}</td>
                            <td class="py-2 pr-4">{{ $doc->content }}</td>
                            <td class="py-2 pr-4">{{ $doc->signer }}</td>
                            <td class="py-2 pr-4">{{ $doc->request_date?->format('d/m/Y') }}</td>
                            <td class="py-2 pr-4 {{ $doc->status->value === 'da_cap' && $doc->expected_return_date?->isPast() ? 'text-red-600 font-medium' : '' }}">
                                {{ $doc->expected_return_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="py-2 pr-4"><x-status-badge :status="$doc->status" /></td>
                            <td class="py-2 pr-4 text-right">
                                @if($doc->status->value === 'da_cap')
                                    <button wire:click="cancel({{ $doc->id }})"
                                            wire:confirm="Bạn có chắc muốn hủy số {{ $doc->formatted_number }}?"
                                            class="text-red-600 hover:underline text-xs">Hủy số</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-slate-400">Chưa có số công văn nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $numbers->links() }}</div>
    </x-card>
</div>
