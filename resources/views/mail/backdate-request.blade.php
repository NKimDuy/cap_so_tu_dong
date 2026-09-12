@component('mail::message')
# Yêu cầu xin số lùi cần được duyệt

Chuyên viên **{{ $req->requester?->name }}** vừa gửi yêu cầu xin **số lùi**:

@component('mail::table')
| Thông tin | Chi tiết |
| :-------- | :------- |
| Nội dung xin số | {{ $req->content }} |
| Người ký số | {{ $req->signer }} |
| Ngày xin số (quá khứ) | {{ $req->requested_date?->format('d/m/Y') }} |
| Ngày dự kiến trả số | {{ $req->expected_return_date?->format('d/m/Y') }} |
@endcomponent

Vui lòng vào hệ thống để chọn một số lùi dự trữ trong kho và cấp cho chuyên viên này.

@component('mail::button', ['url' => route('so.duyet-so-lui')])
Duyệt yêu cầu ngay
@endcomponent

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
