<div class="max-w-5xl space-y-6">
    @include('partials.flash')

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Nhập tài khoản từ file</h1>
            <p class="text-sm text-slate-500">Tạo nhiều tài khoản cùng lúc từ file Excel (.xlsx) hoặc CSV.</p>
        </div>
        <a href="{{ route('admin.nguoi-dung') }}" class="text-sm text-slate-600 hover:text-brand-600">&larr; Về danh sách tài khoản</a>
    </div>

    @if($created)
        <x-card title="Đã tạo {{ count($created) }} tài khoản">
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
                Hãy gửi cho từng người: địa chỉ đăng nhập, email của họ và mật khẩu ban đầu vừa đặt, kèm lời nhắc
                <strong>đăng nhập xong vào "Đổi mật khẩu" để đặt mật khẩu riêng</strong>.
            </div>
            @if($skipped)
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                    Bỏ qua {{ count($skipped) }} email vì vừa có tài khoản trong lúc nhập: {{ implode(', ', $skipped) }}
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-4">Họ tên</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Vai trò</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($created as $u)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4">{{ $u['name'] }}</td>
                                <td class="py-2 pr-4">{{ $u['email'] }}</td>
                                <td class="py-2 pr-4">{{ $u['role_label'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <x-primary-button wire:click="startOver">Nhập file khác</x-primary-button>
            </div>
        </x-card>
    @endif

    @unless($created)
        <x-card title="Bước 1: Chọn file và xem trước">
            <div class="text-sm text-slate-600 space-y-2 mb-4">
                <p>
                    File cần có dòng tiêu đề và 3 cột: <strong>Họ tên</strong>, <strong>Email</strong>, <strong>Vai trò</strong>.
                    Vai trò ghi <em>Chuyên viên</em>, <em>Quản lý</em> hoặc <em>Admin</em>; để trống sẽ là Chuyên viên.
                </p>
                <p>
                    <button type="button" wire:click="downloadTemplate" class="text-brand-600 hover:underline font-medium">Tải file mẫu (Excel)</button>
                    rồi điền danh sách vào, lưu lại và tải lên đây. Mỗi lần tối đa {{ \App\Services\UserImportService::MAX_ROWS }} người.
                </p>
            </div>

            <form wire:submit="preview" class="flex flex-wrap items-start gap-3">
                <div class="flex-1 min-w-[260px]">
                    <input type="file" wire:model="file" accept=".xlsx,.csv"
                           class="block w-full text-sm text-slate-700 border border-slate-300 rounded-lg bg-white file:mr-3 file:px-4 file:py-2 file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <div wire:loading wire:target="file" class="text-xs text-slate-500 mt-1">Đang tải file lên...</div>
                    <x-error for="file" />
                </div>
                <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="file,preview">Xem trước</x-primary-button>
            </form>
        </x-card>
    @endunless

    @if($rows)
        <x-card title="Kết quả kiểm tra: {{ $validCount }} dòng hợp lệ, {{ $errorCount }} dòng lỗi">
            @if($errorCount)
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                    Các dòng lỗi (tô đỏ) sẽ <strong>không</strong> được tạo. Có thể sửa file rồi chọn lại, hoặc vẫn tạo các dòng hợp lệ.
                </div>
            @endif

            <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-4">Dòng</th>
                            <th class="py-2 pr-4">Họ tên</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Vai trò</th>
                            <th class="py-2 pr-4">Kiểm tra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                            <tr class="border-b border-slate-100 {{ $r['errors'] ? 'bg-red-50' : '' }}">
                                <td class="py-2 pr-4 text-slate-400">{{ $r['line'] }}</td>
                                <td class="py-2 pr-4">{{ $r['name'] ?: '-' }}</td>
                                <td class="py-2 pr-4">{{ $r['email'] ?: '-' }}</td>
                                <td class="py-2 pr-4">{{ $r['role_label'] ?: '-' }}</td>
                                <td class="py-2 pr-4">
                                    @if($r['errors'])
                                        <span class="text-red-700">{{ implode(' ', $r['errors']) }}</span>
                                    @else
                                        <span class="text-green-700">Hợp lệ</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        @if($validCount)
            <x-card title="Bước 2: Đặt mật khẩu ban đầu và tạo tài khoản">
                <form wire:submit="import" class="flex flex-wrap items-start gap-3">
                    <div class="w-72">
                        <x-input-label for="password" required>Mật khẩu ban đầu (dùng chung)</x-input-label>
                        <x-text-input type="text" wire:model="password" id="password" autocomplete="off" />
                        <x-error for="password" />
                        <p class="text-xs text-slate-500 mt-1">Tối thiểu 6 ký tự. Mọi người đăng nhập lần đầu rồi tự đổi.</p>
                    </div>
                    <div class="pt-6">
                        <x-primary-button type="submit" wire:loading.attr="disabled"
                                          wire:confirm="Tạo {{ $validCount }} tài khoản với mật khẩu ban đầu này?">
                            Tạo {{ $validCount }} tài khoản
                        </x-primary-button>
                    </div>
                </form>
            </x-card>
        @endif
    @endif
</div>
