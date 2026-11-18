<div class="max-w-3xl space-y-6">
    @include('partials.flash')

    <div>
        <h1 class="text-xl font-semibold text-slate-800">Người ký số</h1>
        <p class="text-sm text-slate-500">Danh sách người ký số để chuyên viên chọn khi xin số. Chỉ những người đang <strong>hiển thị</strong> mới xuất hiện trong ô chọn. Ô chọn luôn có sẵn thêm lựa chọn <strong>"Khác"</strong> cho trường hợp chưa xác định người ký.</p>
    </div>

    <x-card title="Thêm người ký số">
        <form wire:submit="create" class="flex flex-wrap items-start gap-3">
            <div class="flex-1 min-w-[240px]">
                <x-input-label for="name" required>Họ tên (kèm học hàm/chức danh nếu cần)</x-input-label>
                <x-text-input wire:model="name" id="name" placeholder="VD: TS. Nguyễn Văn A" />
                <x-error for="name" />
            </div>
            <div class="w-28">
                <x-input-label for="sort_order">Thứ tự</x-input-label>
                <x-text-input type="number" min="0" wire:model="sort_order" id="sort_order" />
                <x-error for="sort_order" />
            </div>
            <div class="pt-6">
                <x-primary-button type="submit">Thêm</x-primary-button>
            </div>
        </form>
        <p class="text-xs text-slate-500 mt-3">Thứ tự nhỏ hơn được xếp lên trước trong ô chọn; cùng thứ tự thì xếp theo tên.</p>
    </x-card>

    <x-card title="Danh sách người ký số ({{ $signers->count() }})">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4 w-20">Thứ tự</th>
                        <th class="py-2 pr-4">Người ký số</th>
                        <th class="py-2 pr-4">Trạng thái</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($signers as $signer)
                        <tr class="border-b border-slate-100 align-top" wire:key="signer-{{ $signer->id }}">
                            @if($editingId === $signer->id)
                                <td class="py-2 pr-4">
                                    <x-text-input type="number" min="0" wire:model="editSortOrder" />
                                    <x-error for="editSortOrder" />
                                </td>
                                <td class="py-2 pr-4">
                                    <x-text-input wire:model="editName" />
                                    <x-error for="editName" />
                                </td>
                                <td class="py-2 pr-4"></td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                    <button wire:click="update" class="text-brand-600 hover:underline text-xs mr-2">Lưu</button>
                                    <button wire:click="cancelEdit" class="text-slate-500 hover:underline text-xs">Hủy</button>
                                </td>
                            @else
                                <td class="py-2 pr-4">{{ $signer->sort_order }}</td>
                                <td class="py-2 pr-4 {{ $signer->is_active ? '' : 'text-slate-400' }}">{{ $signer->name }}</td>
                                <td class="py-2 pr-4">
                                    @if($signer->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Đang hiển thị</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Đã ẩn</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                    <button wire:click="edit({{ $signer->id }})" class="text-brand-600 hover:underline text-xs mr-2">Sửa</button>
                                    <button wire:click="toggleActive({{ $signer->id }})" class="text-amber-700 hover:underline text-xs mr-2">
                                        {{ $signer->is_active ? 'Ẩn' : 'Hiện' }}
                                    </button>
                                    <button wire:click="delete({{ $signer->id }})"
                                            wire:confirm="Xóa người ký số &quot;{{ $signer->name }}&quot;? Các số đã cấp vẫn giữ nguyên tên người ký."
                                            class="text-red-600 hover:underline text-xs">Xóa</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-400">Chưa có người ký số nào. Hãy thêm ở khung bên trên.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-500 mt-3">Sửa tên, ẩn hoặc xóa người ký <strong>không</strong> làm thay đổi các số đã cấp trước đó; các số cũ vẫn giữ nguyên tên người ký lúc cấp.</p>
    </x-card>
</div>
