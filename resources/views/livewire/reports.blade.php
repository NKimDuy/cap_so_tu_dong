<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Báo cáo & thống kê</h1>
        <p class="text-sm text-slate-500">Tổng số công văn, hồ sơ tồn đọng và thống kê người xin số nhiều nhất.</p>
    </div>

    <x-card>
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <x-input-label>Năm</x-input-label>
                <select wire:model.live="year" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                    @if($availableYears->isEmpty())
                        <option value="{{ now()->year }}">{{ now()->year }}</option>
                    @endif
                </select>
            </div>
            <div>
                <x-input-label>Từ ngày ký (thống kê top người xin)</x-input-label>
                <x-text-input type="date" wire:model.live="fromDate" />
            </div>
            <div>
                <x-input-label>Đến ngày ký</x-input-label>
                <x-text-input type="date" wire:model.live="toDate" />
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <x-card title="Tổng số công văn năm {{ $year }}">
            <div class="text-3xl font-semibold text-slate-800">{{ $totalThisYear }}</div>
        </x-card>
        <x-card title="Hồ sơ chưa trả">
            <div class="text-3xl font-semibold text-red-600">{{ $outstanding->count() }}</div>
        </x-card>
        <x-card title="Người xin nhiều nhất">
            <div class="text-lg font-semibold text-slate-800">{{ $topRequesters->first()?->requester?->name ?? '-' }}</div>
            <div class="text-sm text-slate-500">{{ $topRequesters->first()?->total ?? 0 }} lần xin số</div>
        </x-card>
    </div>

    <x-card title="Số công văn theo tháng (theo ngày ghi nhận/ngày ký) - năm {{ $year }}">
        <div class="grid grid-cols-3 sm:grid-cols-6 lg:grid-cols-12 gap-3">
            @foreach($monthly as $m)
                <div class="text-center">
                    <div class="h-20 flex items-end justify-center">
                        <div class="w-6 bg-brand-500 rounded-t" style="height: {{ $m['total'] > 0 ? max(8, min(80, $m['total'] * 8)) : 2 }}px"></div>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Th{{ $m['month'] }}</div>
                    <div class="text-xs font-medium text-slate-700">{{ $m['total'] }}</div>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card title="Top người xin số nhiều nhất">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">#</th>
                        <th class="py-2 pr-4">Chuyên viên</th>
                        <th class="py-2 pr-4">Số lần xin số</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topRequesters as $i => $row)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4">{{ $i + 1 }}</td>
                            <td class="py-2 pr-4">{{ $row->requester?->name ?? '-' }}</td>
                            <td class="py-2 pr-4 font-medium">{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-slate-400">Chưa có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card title="Danh sách hồ sơ chưa trả">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Số</th>
                        <th class="py-2 pr-4">Năm</th>
                        <th class="py-2 pr-4">Ngày ghi nhận/Ngày ký</th>
                        <th class="py-2 pr-4">Người xin</th>
                        <th class="py-2 pr-4">Nội dung</th>
                        <th class="py-2 pr-4">Ngày dự kiến trả</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($outstanding as $doc)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 font-semibold">{{ $doc->number_display }}</td>
                            <td class="py-2 pr-4 text-slate-500">{{ $doc->year }}</td>
                            <td class="py-2 pr-4"><x-date-time :value="$doc->signed_at" :time="$doc->signed_has_time" /></td>
                            <td class="py-2 pr-4">{{ $doc->requester?->name }}</td>
                            <td class="py-2 pr-4">{{ $doc->content }}</td>
                            <td class="py-2 pr-4 {{ $doc->expected_return_date?->isPast() ? 'text-red-600 font-medium' : '' }}">
                                {{ $doc->expected_return_date?->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-400">Không có hồ sơ nào chưa trả.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
