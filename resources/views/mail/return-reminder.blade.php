@component('mail::message')
# Nhắc trả số công văn

Xin chào **{{ $doc->requester?->name }}**,

Số công văn **{{ $doc->formatted_number }}** ({{ $doc->content }}) đã đến ngày dự kiến trả
({{ $doc->expected_return_date?->format('d/m/Y') }}) nhưng chưa được xác nhận hoàn thành trên hệ thống.

Vui lòng hoàn tất hồ sơ và trả giấy tờ cho quản lý sớm nhất có thể. Hệ thống sẽ tiếp tục nhắc lại mỗi tuần cho đến khi số này được xác nhận hoàn thành.

@component('mail::button', ['url' => route('dashboard')])
Xem số của tôi
@endcomponent

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
