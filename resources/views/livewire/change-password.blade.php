<div class="max-w-md space-y-6">
    @include('partials.flash')

    <div>
        <h1 class="text-xl font-semibold text-slate-800">Đổi mật khẩu</h1>
        <p class="text-sm text-slate-500">Nếu quên mật khẩu, vui lòng liên hệ Admin để được cấp lại.</p>
    </div>

    <x-card>
        <form wire:submit="save" class="space-y-4">
            <div>
                <x-input-label for="current_password" required>Mật khẩu hiện tại</x-input-label>
                <x-text-input type="password" wire:model="current_password" id="current_password" autocomplete="current-password" />
                <x-error for="current_password" />
            </div>
            <div>
                <x-input-label for="password" required>Mật khẩu mới</x-input-label>
                <x-text-input type="password" wire:model="password" id="password" autocomplete="new-password" />
                <x-error for="password" />
                <p class="text-xs text-slate-500 mt-1">Tối thiểu 6 ký tự, khác mật khẩu hiện tại.</p>
            </div>
            <div>
                <x-input-label for="password_confirmation" required>Nhập lại mật khẩu mới</x-input-label>
                <x-text-input type="password" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password" />
            </div>
            <x-primary-button type="submit" wire:loading.attr="disabled">Đổi mật khẩu</x-primary-button>
        </form>
    </x-card>
</div>
