<?php

namespace App\Livewire;

use App\Enums\SystemLogAction;
use App\Models\Setting;
use App\Models\SystemLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SystemSettings extends Component
{
    public int $max_pending_numbers = 3;
    public int $reminder_interval_days = 7;

    public function mount(): void
    {
        $this->max_pending_numbers = (int) Setting::get('max_pending_numbers', 3);
        $this->reminder_interval_days = (int) Setting::get('reminder_interval_days', 7);
    }

    protected function rules(): array
    {
        return [
            'max_pending_numbers' => ['required', 'integer', 'min:1', 'max:20'],
            'reminder_interval_days' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('max_pending_numbers', $this->max_pending_numbers);
        Setting::set('reminder_interval_days', $this->reminder_interval_days);

        SystemLog::record(
            auth()->id(),
            SystemLogAction::CapNhatCauHinh,
            'Cập nhật cấu hình hệ thống.',
            ['max_pending_numbers' => $this->max_pending_numbers, 'reminder_interval_days' => $this->reminder_interval_days]
        );

        session()->flash('success', 'Đã lưu cấu hình hệ thống.');
    }

    public function render()
    {
        return view('livewire.system-settings');
    }
}
