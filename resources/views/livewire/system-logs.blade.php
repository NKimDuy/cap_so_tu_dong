<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Nhật ký hệ thống</h1>
        <p class="text-sm text-slate-500">Toàn bộ thao tác trên hệ thống kèm người thực hiện và thời gian.</p>
    </div>

    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <x-input-label>Người thực hiện</x-input-label>
                <select wire:model.live="userFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label>Hành động</x-input-label>
                <select wire:model.live="actionFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($actions as $a)
                        <option value="{{ $a->value }}">{{ $a->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label>Từ ngày</x-input-label>
                <x-text-input type="date" wire:model.live="fromDate" />
            </div>
            <div>
                <x-input-label>Đến ngày</x-input-label>
                <x-text-input type="date" wire:model.live="toDate" />
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Thời gian</th>
                        <th class="py-2 pr-4">Người thực hiện</th>
                        <th class="py-2 pr-4">Hành động</th>
                        <th class="py-2 pr-4">Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-2 pr-4 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="py-2 pr-4">{{ $log->user?->name ?? 'Hệ thống' }}</td>
                            <td class="py-2 pr-4">{{ $log->action_label }}</td>
                            <td class="py-2 pr-4 text-slate-600">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-400">Chưa có nhật ký nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </x-card>
</div>
