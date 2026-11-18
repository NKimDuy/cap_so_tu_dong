<div class="space-y-6">
    @include('partials.flash')

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Số của tôi</h1>
            <p class="text-sm text-slate-500">Lịch sử các số công văn bạn đã xin và trạng thái hiện tại.</p>
        </div>
        <a href="{{ route('so.xin-so') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700">+ Xin số mới</a>
    </div>

    <div class="flex gap-2 items-center">
        <label class="text-sm text-slate-600">Lọc trạng thái:</label>
        <select wire:model.live="statusFilter" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
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
                        <th class="py-2 pr-4">Năm</th>
                        <th class="py-2 pr-4">Ngày ghi nhận/<br>Ngày ký</th>
                        <th class="py-2 pr-4">Ngày cấp<br>số lùi</th>
                        <th class="py-2 pr-4 min-w-[14rem]">Nội dung</th>
                        <th class="py-2 pr-4 min-w-[9rem]">Người ký</th>
                        <th class="py-2 pr-4">Hạn trả</th>
                        <th class="py-2 pr-4">Ngày hoàn thành</th>
                        <th class="py-2 pr-4">Trạng thái</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($numbers as $doc)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4">
                                <div class="font-semibold text-base">{{ $doc->number_display }}</div>
                                @if($doc->is_backdate)
                                    <div class="text-[11px] leading-tight text-amber-700 whitespace-nowrap">Số lùi</div>
                                @endif
                            </td>
                            <td class="py-2 pr-4 text-slate-500">{{ $doc->year }}</td>
                            <td class="py-2 pr-4"><x-date-time :value="$doc->signed_at" :time="$doc->signed_has_time" /></td>
                            <td class="py-2 pr-4"><x-date-time :value="$doc->backdate_issued_at" /></td>
                            <td class="py-2 pr-4">
                                {{ $doc->content }}
                                @if($doc->registeredBy)
                                    <div class="text-xs text-slate-400">Do {{ $doc->registeredBy->name }} đăng ký hộ</div>
                                @endif
                            </td>
                            <td class="py-2 pr-4">{{ $doc->signer }}</td>
                            <td class="py-2 pr-4 {{ $doc->status->value === 'da_cap' && $doc->expected_return_date?->isPast() ? 'text-red-600 font-medium' : '' }}">
                                {{ $doc->expected_return_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="py-2 pr-4"><x-date-time :value="$doc->completed_at" /></td>
                            <td class="py-2 pr-4"><x-status-badge :status="$doc->status" /></td>
                            <td class="py-2 pr-4 text-right whitespace-nowrap">
                                @if($doc->status->value === 'da_cap')
                                    <button wire:click="cancel({{ $doc->id }})"
                                            wire:confirm="Bạn có chắc muốn hủy số {{ $doc->formatted_number }}?"
                                            class="text-red-600 hover:underline text-xs">Hủy số</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-6 text-center text-slate-400">Chưa có số công văn nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $numbers->links() }}</div>
    </x-card>
</div>
