<div class="space-y-6">
    @include('partials.flash')

    <div>
        <h1 class="text-xl font-semibold text-slate-800">Tất cả số công văn</h1>
        <p class="text-sm text-slate-500">Theo dõi toàn bộ số công văn, sửa thông tin, xác nhận hoàn thành hoặc hủy số.</p>
    </div>

    @if($editingDoc)
        <x-card title="Sửa thông tin số {{ $editingDoc->formatted_number }}">
            <form wire:submit="saveEdit" class="space-y-4">
                <div class="text-sm text-slate-500">
                    Người xin: <span class="text-slate-800">{{ $editingDoc->requester?->name ?? '-' }}</span>
                    &middot; Trạng thái: <x-status-badge :status="$editingDoc->status" />
                </div>
                <div>
                    <x-input-label for="editContent" required>Nội dung xin số</x-input-label>
                    <x-text-input wire:model="editContent" id="editContent" />
                    <x-error for="editContent" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="editSigner" required>Người ký số</x-input-label>
                        <select wire:model="editSigner" id="editSigner"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                            <option value="">-- Chọn người ký số --</option>
                            @foreach($editSignerOptions as $name)
                                <option value="{{ $name }}">{{ $name === \App\Models\Signer::OTHER ? 'Khác (chưa xác định)' : $name }}</option>
                            @endforeach
                        </select>
                        <x-error for="editSigner" />
                    </div>
                    <div>
                        <x-input-label for="editExpectedReturnDate" required>Ngày dự kiến trả số</x-input-label>
                        <x-text-input type="date" wire:model="editExpectedReturnDate" id="editExpectedReturnDate" />
                        <x-error for="editExpectedReturnDate" />
                    </div>
                </div>
                <p class="text-xs text-slate-500">Mọi lần sửa đều được ghi vào Nhật ký hệ thống (người sửa, giá trị cũ và mới).</p>
                <div class="flex gap-2">
                    <x-primary-button type="submit" wire:loading.attr="disabled">Lưu thay đổi</x-primary-button>
                    <button type="button" wire:click="cancelEdit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">Hủy</button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <x-input-label>Tìm kiếm</x-input-label>
                <x-text-input wire:model.live.debounce.400ms="search" placeholder="Nội dung / người ký..." />
            </div>
            <div>
                <x-input-label>Trạng thái</x-input-label>
                <select wire:model.live="statusFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả</option>
                    <option value="da_cap">Đã cấp - Chờ trả</option>
                    <option value="hoan_thanh">Hoàn thành</option>
                    <option value="so_lui_du_tru">Số lùi dự trữ</option>
                </select>
            </div>
            <div>
                <x-input-label>Người xin</x-input-label>
                <select wire:model.live="requesterFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($requesters as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label>Năm</x-input-label>
                <select wire:model.live="yearFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả năm</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label>Ngày ghi nhận/Ngày ký: từ - đến</x-input-label>
                <div class="grid grid-cols-2 gap-2">
                    <x-text-input type="date" wire:model.live="fromDate" class="min-w-0" />
                    <x-text-input type="date" wire:model.live="toDate" class="min-w-0" />
                </div>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-2.5">Số</th>
                        <th class="py-2 pr-2.5">Năm</th>
                        <th class="py-2 pr-2.5">Ngày ghi nhận/<br>Ngày ký</th>
                        <th class="py-2 pr-2.5">Ngày cấp<br>số lùi</th>
                        <th class="py-2 pr-2.5 min-w-[7rem]">Người xin</th>
                        <th class="py-2 pr-2.5 min-w-[9rem]">Nội dung</th>
                        <th class="py-2 pr-2.5 min-w-[7rem]">Người ký</th>
                        <th class="py-2 pr-2.5">Hạn trả</th>
                        <th class="py-2 pr-2.5">Ngày hoàn thành</th>
                        <th class="py-2 pr-2.5">Trạng thái</th>
                        <th class="py-2 pr-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($numbers as $doc)
                        <tr class="border-b border-slate-100 {{ $editingId === $doc->id ? 'bg-brand-50' : '' }}" wire:key="doc-{{ $doc->id }}">
                            <td class="py-2 pr-2.5">
                                <div class="font-semibold text-base">{{ $doc->number_display }}</div>
                                @if($doc->is_backdate)
                                    <div class="text-[11px] leading-tight text-amber-700 whitespace-nowrap">Số lùi</div>
                                @endif
                            </td>
                            <td class="py-2 pr-2.5 text-slate-500">{{ $doc->year }}</td>
                            <td class="py-2 pr-2.5"><x-date-time :value="$doc->signed_at" :time="$doc->signed_has_time" /></td>
                            <td class="py-2 pr-2.5"><x-date-time :value="$doc->backdate_issued_at" /></td>
                            <td class="py-2 pr-2.5">
                                {{ $doc->requester?->name ?? '-' }}
                                @if($doc->registeredBy)
                                    <div class="text-xs text-slate-400">Đăng ký hộ: {{ $doc->registeredBy->name }}</div>
                                @endif
                            </td>
                            <td class="py-2 pr-2.5">{{ $doc->content ?? '-' }}</td>
                            <td class="py-2 pr-2.5">{{ $doc->signer ?? '-' }}</td>
                            <td class="py-2 pr-2.5 {{ $doc->status->value === 'da_cap' && $doc->expected_return_date?->isPast() ? 'text-red-600 font-medium' : '' }}">
                                {{ $doc->expected_return_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="py-2 pr-2.5">
                                <x-date-time :value="$doc->completed_at" />
                                @if($doc->completed_at && $doc->completedBy)
                                    <div class="text-xs text-slate-400 whitespace-nowrap">{{ $doc->completedBy->name }}</div>
                                @endif
                            </td>
                            <td class="py-2 pr-2.5"><x-status-badge :status="$doc->status" /></td>
                            <td class="py-2 text-right whitespace-nowrap">
                                <div class="flex flex-col items-end gap-1">
                                    @if(in_array($doc->status->value, ['da_cap', 'hoan_thanh'], true))
                                        <button wire:click="edit({{ $doc->id }})"
                                                class="text-brand-600 hover:underline text-xs">Sửa</button>
                                    @endif
                                    @if($doc->status->value === 'da_cap')
                                        <button wire:click="complete({{ $doc->id }})"
                                                wire:confirm="Xác nhận chuyên viên đã trả số {{ $doc->formatted_number }}?"
                                                class="text-green-600 hover:underline text-xs">Hoàn thành</button>
                                        <button wire:click="cancel({{ $doc->id }})"
                                                wire:confirm="Bạn có chắc muốn hủy số {{ $doc->formatted_number }}?"
                                                class="text-red-600 hover:underline text-xs">Hủy số</button>
                                    @endif
                                    @if($doc->status->value === 'hoan_thanh')
                                        <button wire:click="reopen({{ $doc->id }})"
                                                wire:confirm="Mở lại số {{ $doc->formatted_number }}? Số sẽ trở về trạng thái 'Đã cấp - Chờ trả' và xóa ngày hoàn thành."
                                                class="text-amber-600 hover:underline text-xs">Mở lại</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="py-6 text-center text-slate-400">Không có dữ liệu phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $numbers->links() }}</div>
    </x-card>
</div>
