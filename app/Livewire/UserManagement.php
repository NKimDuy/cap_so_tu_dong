<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasFlashMessages;
use App\Enums\SystemLogAction;
use App\Enums\UserRole;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UserManagement extends Component
{
    use HasFlashMessages;
    use WithPagination;

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'chuyen_vien';

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'role']);
        $this->role = 'chuyen_vien';
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role->value;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['editingId', 'name', 'email', 'password', 'role']);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:6'],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ];
    }

    protected array $messages = [
        'name.required' => 'Vui lòng nhập họ tên.',
        'email.required' => 'Vui lòng nhập email.',
        'email.unique' => 'Email này đã được sử dụng.',
        'password.required' => 'Vui lòng nhập mật khẩu.',
        'password.min' => 'Mật khẩu tối thiểu 6 ký tự.',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->role = $this->role;
            if ($this->password) {
                $user->password = Hash::make($this->password);
            }
            $user->save();

            SystemLog::record(auth()->id(), SystemLogAction::SuaTaiKhoan, "Cập nhật tài khoản {$user->email}.", ['user_id' => $user->id]);
            $this->notifySuccess('Đã cập nhật tài khoản.');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'is_active' => true,
            ]);

            SystemLog::record(auth()->id(), SystemLogAction::TaoTaiKhoan, "Tạo tài khoản {$user->email}.", ['user_id' => $user->id]);
            $this->notifySuccess('Đã tạo tài khoản mới.');
        }

        $this->cancel();
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->notifyError('Bạn không thể tự khóa tài khoản của chính mình.');

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        SystemLog::record(
            auth()->id(),
            $user->is_active ? SystemLogAction::MoKhoaTaiKhoan : SystemLogAction::KhoaTaiKhoan,
            ($user->is_active ? 'Mở khóa' : 'Khóa')." tài khoản {$user->email}.",
            ['user_id' => $user->id]
        );

        $this->notifySuccess($user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }

    public function render()
    {
        return view('livewire.user-management', [
            'users' => User::orderBy('name')->paginate(15),
        ]);
    }
}
