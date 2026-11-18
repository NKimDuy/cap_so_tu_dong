<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasFlashMessages;
use App\Enums\SystemLogAction;
use App\Models\Signer;
use App\Models\SystemLog;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Cài đặt danh sách người ký số (Quản lý và Admin).
 * Tên người ký được lưu thẳng vào từng số khi cấp, nên sửa/ẩn/xóa ở đây không đổi các số đã cấp.
 */
#[Layout('layouts.app')]
class SignerManagement extends Component
{
    use HasFlashMessages;

    // Form thêm mới
    public string $name = '';
    public int $sort_order = 0;

    // Đang sửa dòng nào
    public ?int $editingId = null;
    public string $editName = '';
    public int $editSortOrder = 0;

    protected array $messages = [
        'name.required' => 'Vui lòng nhập tên người ký số.',
        'name.unique' => 'Người ký số này đã có trong danh sách.',
        'editName.required' => 'Vui lòng nhập tên người ký số.',
        'editName.unique' => 'Người ký số này đã có trong danh sách.',
        'name.not_in' => 'Tên "Khác" là lựa chọn có sẵn của hệ thống, không cần thêm.',
        'editName.not_in' => 'Tên "Khác" là lựa chọn có sẵn của hệ thống, không cần thêm.',
        'sort_order.min' => 'Thứ tự phải từ 0 trở lên.',
        'editSortOrder.min' => 'Thứ tự phải từ 0 trở lên.',
    ];

    public function create(): void
    {
        $this->name = trim($this->name);

        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('signers', 'name'), Rule::notIn([Signer::OTHER])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $signer = Signer::create([
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'is_active' => true,
        ]);

        SystemLog::record(auth()->id(), SystemLogAction::ThemNguoiKy, auth()->user()->name." thêm người ký số \"{$signer->name}\".", ['signer_id' => $signer->id]);

        $this->reset(['name', 'sort_order']);
        $this->notifySuccess("Đã thêm người ký số \"{$signer->name}\".");
    }

    public function edit(int $id): void
    {
        $signer = Signer::findOrFail($id);

        $this->resetErrorBag();
        $this->editingId = $signer->id;
        $this->editName = $signer->name;
        $this->editSortOrder = $signer->sort_order;
    }

    public function cancelEdit(): void
    {
        $this->resetErrorBag();
        $this->reset(['editingId', 'editName', 'editSortOrder']);
    }

    public function update(): void
    {
        $signer = Signer::findOrFail($this->editingId);
        $this->editName = trim($this->editName);

        $this->validate([
            'editName' => ['required', 'string', 'max:255', Rule::unique('signers', 'name')->ignore($signer->id), Rule::notIn([Signer::OTHER])],
            'editSortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $old = ['name' => $signer->name, 'sort_order' => $signer->sort_order];
        $signer->update(['name' => $this->editName, 'sort_order' => $this->editSortOrder]);

        SystemLog::record(auth()->id(), SystemLogAction::SuaNguoiKy, auth()->user()->name." sửa người ký số \"{$old['name']}\".", [
            'signer_id' => $signer->id,
            'old' => $old,
            'new' => ['name' => $signer->name, 'sort_order' => $signer->sort_order],
        ]);

        $this->cancelEdit();
        $this->notifySuccess('Đã cập nhật người ký số.');
    }

    public function toggleActive(int $id): void
    {
        $signer = Signer::findOrFail($id);
        $signer->update(['is_active' => ! $signer->is_active]);

        $state = $signer->is_active ? 'hiện' : 'ẩn';

        SystemLog::record(auth()->id(), SystemLogAction::SuaNguoiKy, auth()->user()->name." {$state} người ký số \"{$signer->name}\".", [
            'signer_id' => $signer->id,
            'is_active' => $signer->is_active,
        ]);

        $this->notifySuccess("Đã {$state} người ký số \"{$signer->name}\".");
    }

    public function delete(int $id): void
    {
        $signer = Signer::findOrFail($id);
        $name = $signer->name;
        $signer->delete();

        if ($this->editingId === $id) {
            $this->cancelEdit();
        }

        SystemLog::record(auth()->id(), SystemLogAction::XoaNguoiKy, auth()->user()->name." xóa người ký số \"{$name}\".", ['signer_id' => $id, 'name' => $name]);

        $this->notifySuccess("Đã xóa người ký số \"{$name}\".");
    }

    public function render()
    {
        return view('livewire.signer-management', [
            'signers' => Signer::ordered()->get(),
        ]);
    }
}
