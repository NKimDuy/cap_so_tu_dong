@component('mail::message')
# Số công văn đã được cấp

Xin chào **{{ $doc->requester?->name }}**,

Số công văn của bạn đã được cấp thành công với thông tin như sau:

@component('mail::table')
| Thông tin | Chi tiết |
| :-------- | :------- |
| Số công văn | **{{ $doc->formatted_number }}** |
| Loại | {{ $doc->is_backdate ? 'Số lùi' : 'Bình thường' }} |
| Nội dung xin số | {{ $doc->content }} |
| Người ký số | {{ $doc->signer }} |
| Ngày xin số | {{ $doc->request_date?->format('d/m/Y') }} |
| Ngày dự kiến trả số | {{ $doc->expected_return_date?->format('d/m/Y') }} |
@endcomponent

Lưu ý: bạn hiện được phép giữ tối đa 3 số chưa trả cùng lúc. Vui lòng trả giấy tờ đúng hạn để có thể tiếp tục xin số mới.

@component('mail::button', ['url' => route('dashboard')])
Xem trên hệ thống
@endcomponent

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
