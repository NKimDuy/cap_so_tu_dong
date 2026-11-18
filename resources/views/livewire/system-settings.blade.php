<div class="max-w-xl space-y-6">
    @include('partials.flash')

    <div>
        <h1 class="text-xl font-semibold text-slate-800">Cấu hình hệ thống</h1>
        <p class="text-sm text-slate-500">Năm hiện hành và số reset về 1 được hệ thống tự động xử lý theo năm dương lịch.</p>
    </div>

    <x-card>
        <div class="mb-4 rounded-lg bg-slate-50 border border-slate-200 text-slate-600 text-sm px-4 py-3">
            Năm cấp số hiện tại của hệ thống: <strong>{{ now()->year }}</strong> (tự động reset về số 1 vào ngày 01/01 hàng năm).
            <br>Cấu hình gửi email (SMTP) được thiết lập trong file <code>.env</code> trên máy chủ.
        </div>

        <form wire:submit="save" class="space-y-4">
            <div>
                <x-input-label required>Giới hạn số chưa trả tối đa / chuyên viên</x-input-label>
                <x-text-input type="number" min="1" max="20" wire:model="max_pending_numbers" />
                <x-error for="max_pending_numbers" />
                <p class="text-xs text-slate-500 mt-1">Mặc định theo kế hoạch là 3. Chuyên viên đạt giới hạn này sẽ không thể xin thêm số mới.</p>
            </div>
            <div>
                <x-input-label required>Chu kỳ nhắc trả số lại (ngày)</x-input-label>
                <x-text-input type="number" min="1" max="30" wire:model="reminder_interval_days" />
                <x-error for="reminder_interval_days" />
                <p class="text-xs text-slate-500 mt-1">Mặc định theo kế hoạch là 7 ngày (nhắc lại mỗi tuần cho đến khi hoàn thành).</p>
            </div>
            <x-primary-button type="submit">Lưu cấu hình</x-primary-button>
        </form>
    </x-card>
</div>
