@component('mail::message')
# Thông báo theo dõi: có yêu cầu xin số mới

Chuyên viên **{{ $doc->requester?->name }}** vừa xin số công văn bình thường:

@component('mail::table')
| Thông tin | Chi tiết |
| :-------- | :------- |
| Số công văn | **{{ $doc->formatted_number }}** |
| Nội dung xin số | {{ $doc->content }} |
| Người ký số | {{ $doc->signer }} |
| Ngày dự kiến trả số | {{ $doc->expected_return_date?->format('d/m/Y') }} |
@endcomponent

Đây là email theo dõi tự động, không cần thao tác gì thêm.

@component('mail::button', ['url' => route('so.tat-ca')])
Xem danh sách số công văn
@endcomponent

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
