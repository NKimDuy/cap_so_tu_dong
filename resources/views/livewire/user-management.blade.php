<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Quản lý tài khoản</h1>
            <p class="text-sm text-slate-500">Tạo, chỉnh sửa, phân quyền và khóa/mở khóa tài khoản người dùng.</p>
        </div>
        @unless($showForm)
            <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700">+ Tạo tài khoản</button>
        @endunless
    </div>

    @if($showForm)
        <x-card :title="$editingId ? 'Chỉnh sửa tài khoản' : 'Tạo tài khoản mới'">
            <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label required>Họ tên</x-input-label>
                    <x-text-input wire:model="name" />
                    <x-error for="name" />
                </div>
                <div>
                    <x-input-label required>Email</x-input-label>
                    <x-text-input type="email" wire:model="email" />
                    <x-error for="email" />
                </div>
                <div>
                    <x-input-label :required="!$editingId">Mật khẩu {{ $editingId ? '(để trống nếu không đổi)' : '' }}</x-input-label>
                    <x-text-input type="password" wire:model="password" />
                    <x-error for="password" />
                </div>
                <div>
                    <x-input-label required>Vai trò</x-input-label>
                    <select wire:model="role" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach(\App\Enums\UserRole::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 flex gap-3 pt-2">
                    <x-primary-button type="submit">Lưu</x-primary-button>
                    <button type="button" wire:click="cancel" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Hủy</button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Họ tên</th>
                        <th class="py-2 pr-4">Email</th>
                        <th class="py-2 pr-4">Vai trò</th>
                        <th class="py-2 pr-4">Trạng thái</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 font-medium">{{ $u->name }}</td>
                            <td class="py-2 pr-4">{{ $u->email }}</td>
                            <td class="py-2 pr-4">{{ $u->role->label() }}</td>
                            <td class="py-2 pr-4">
                                @if($u->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Đang hoạt động</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-200 text-slate-600">Đã khóa</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4 text-right whitespace-nowrap">
                                <button wire:click="edit({{ $u->id }})" class="text-brand-600 hover:underline text-xs mr-3">Sửa</button>
                                @if($u->id !== auth()->id())
                                    <button wire:click="toggleActive({{ $u->id }})"
                                            wire:confirm="Xác nhận {{ $u->is_active ? 'khóa' : 'mở khóa' }} tài khoản {{ $u->email }}?"
                                            class="text-xs {{ $u->is_active ? 'text-red-600' : 'text-green-600' }} hover:underline">
                                        {{ $u->is_active ? 'Khóa' : 'Mở khóa' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </x-card>
</div>
