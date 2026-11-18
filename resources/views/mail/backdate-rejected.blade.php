@component('mail::message')
# Yêu cầu xin số lùi đã bị từ chối

Xin chào **{{ $req->requester?->name }}**,

Yêu cầu xin số lùi của bạn (nội dung: "{{ $req->content }}", ngày ghi nhận/ngày ký {{ $req->requested_date?->format('d/m/Y') }}) đã bị từ chối.

@if($req->reject_reason)
**Lý do:** {{ $req->reject_reason }}
@endif

Vui lòng liên hệ quản lý để biết thêm chi tiết hoặc gửi lại yêu cầu nếu cần.

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
