<?php

namespace App\Livewire\Concerns;

/**
 * Thông báo thành công / lỗi hiển thị ngay trong trang sau khi bấm nút.
 *
 * Lưu ý: session()->flash() chỉ hiện ở lần TẢI LẠI TRANG kế tiếp (banner trong layout),
 * còn khi Livewire cập nhật một phần trang thì layout không được vẽ lại nên thông báo không hiện.
 * Vì vậy các thao tác không chuyển trang dùng 2 thuộc tính bên dưới,
 * và view của component phải @include('partials.flash').
 */
trait HasFlashMessages
{
    public string $flashSuccess = '';
    public string $flashError = '';

    protected function notifySuccess(string $message): void
    {
        $this->flashError = '';
        $this->flashSuccess = $message;
    }

    protected function notifyError(string $message): void
    {
        $this->flashSuccess = '';
        $this->flashError = $message;
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = '';
        $this->flashError = '';
    }
}
