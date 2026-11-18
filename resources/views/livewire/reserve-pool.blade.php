<div class="space-y-6">
    @include('partials.flash')

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

    <x-card title="Nhập số dự trữ cũ (đã giữ trước khi dùng hệ thống)">
        <form wire:submit="addOld" class="flex flex-wrap items-start gap-3">
            <div class="w-28">
                <x-input-label for="oldYear" required>Năm</x-input-label>
                <x-text-input type="number" wire:model="oldYear" id="oldYear" />
                <x-error for="oldYear" />
            </div>
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="oldNumbers" required>Số (nhiều số cách nhau bằng dấu phẩy)</x-input-label>
                <x-text-input wire:model="oldNumbers" id="oldNumbers" placeholder="VD: 188, 192, 194" />
                <x-error for="oldNumbers" />
            </div>
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="oldNotes">Ghi chú (không bắt buộc)</x-input-label>
                <x-text-input wire:model="oldNotes" id="oldNotes" placeholder="VD: số dự phòng ghi trên Google Form" />
            </div>
            <div class="pt-6">
                <x-primary-button type="submit">Nhập vào kho</x-primary-button>
            </div>
        </form>
        <p class="text-xs text-slate-500 mt-3">
            Dùng cho các số dự phòng đã giữ trên sổ/Google Form trước đây. Hệ thống không cho nhập số đã có, và với năm hiện tại chỉ nhận số
            nhỏ hơn số tiếp theo sẽ cấp. Số của năm trước chỉ cấp được cho yêu cầu số lùi có ngày ký trong năm đó.
        </p>
    </x-card>

    <x-card title="Danh sách số lùi dự trữ hiện có ({{ $pool->count() }})">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Số</th>
                        <th class="py-2 pr-4">Năm</th>
                        <th class="py-2 pr-4">Người giữ</th>
                        <th class="py-2 pr-4">Ngày giữ</th>
                        <th class="py-2 pr-4">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pool as $doc)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 font-semibold text-base">{{ $doc->number_display }}</td>
                            <td class="py-2 pr-4 text-slate-500">{{ $doc->year }}</td>
                            <td class="py-2 pr-4">{{ $doc->heldBy?->name ?? '-' }}</td>
                            <td class="py-2 pr-4"><x-date-time :value="$doc->record_date" /></td>
                            <td class="py-2 pr-4 text-slate-500">{{ $doc->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">Kho số lùi dự trữ đang trống.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
