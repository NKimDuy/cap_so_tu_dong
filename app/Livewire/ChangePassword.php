<?php

namespace App\Livewire;

use App\Enums\SystemLogAction;
use App\Livewire\Concerns\HasFlashMessages;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Người dùng (mọi vai trò) tự đổi mật khẩu của mình.
 * Quên mật khẩu thì Admin cấp lại trong mục "Tài khoản người dùng".
 */
#[Layout('layouts.app')]
class ChangePassword extends Component
{
    use HasFlashMessages;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ];
    }

    protected array $messages = [
        'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
        'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
        'password.required' => 'Vui lòng nhập mật khẩu mới.',
        'password.min' => 'Mật khẩu mới tối thiểu 6 ký tự.',
        'password.confirmed' => 'Nhập lại mật khẩu mới không khớp.',
        'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
    ];

    public function save(): void
    {
        $this->validate();

        $user = auth()->user();
        $user->password = Hash::make($this->password);
        $user->save();

        // Đăng xuất tài khoản này trên các máy/trình duyệt khác (phiên hiện tại vẫn giữ đăng nhập).
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        SystemLog::record($user->id, SystemLogAction::DoiMatKhau, "{$user->name} đã tự đổi mật khẩu.");

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->notifySuccess('Đã đổi mật khẩu thành công. Lần đăng nhập sau hãy dùng mật khẩu mới.');
    }

    public function render()
    {
        return view('livewire.change-password');
    }
}
