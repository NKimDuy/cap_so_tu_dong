<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Services\DocumentNumberService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RequestNumber extends Component
{
    public string $mode = 'binh_thuong'; // binh_thuong | so_lui

    public string $content = '';
    public string $signer = '';
    public string $expected_return_date = '';
    public string $requested_date = '';

    protected function rules(): array
    {
        $rules = [
            'content' => ['required', 'string', 'max:255'],
            'signer' => ['required', 'string', 'max:255'],
            'expected_return_date' => ['required', 'date', 'after_or_equal:today'],
        ];

        if ($this->mode === 'so_lui') {
            $rules['requested_date'] = ['required', 'date', 'before:today'];
        }

        return $rules;
    }

    protected array $messages = [
        'content.required' => 'Vui lòng nhập nội dung xin số.',
        'signer.required' => 'Vui lòng nhập người ký số.',
        'expected_return_date.required' => 'Vui lòng chọn ngày dự kiến trả số.',
        'expected_return_date.after_or_equal' => 'Ngày dự kiến trả số không được ở trong quá khứ.',
        'requested_date.required' => 'Vui lòng chọn ngày xin số (ngày quá khứ).',
        'requested_date.before' => 'Ngày xin số lùi phải là một ngày trong quá khứ.',
    ];

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
        $this->resetErrorBag();
    }

    public function submit(DocumentNumberService $service): void
    {
        $this->validate();

        try {
            if ($this->mode === 'binh_thuong') {
                $doc = $service->issueNormal(auth()->user(), [
                    'content' => $this->content,
                    'signer' => $this->signer,
                    'expected_return_date' => $this->expected_return_date,
                ]);

                session()->flash('success', "Đã cấp số {$doc->formatted_number} thành công. Email xác nhận đã được gửi.");
            } else {
                $service->requestBackdate(auth()->user(), [
                    'content' => $this->content,
                    'signer' => $this->signer,
                    'requested_date' => $this->requested_date,
                    'expected_return_date' => $this->expected_return_date,
                ]);

                session()->flash('success', 'Đã gửi yêu cầu xin số lùi. Quản lý sẽ xem xét và cấp số từ kho dự trữ.');
            }

            $this->reset(['content', 'signer', 'expected_return_date', 'requested_date']);

            $this->redirect(route('so.cua-toi'), navigate: false);
        } catch (BusinessRuleException $e) {
            $this->addError('form', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.request-number');
    }
}
