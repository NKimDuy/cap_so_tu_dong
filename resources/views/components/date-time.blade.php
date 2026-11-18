@props(['value' => null, 'time' => true])
{{-- Ngày ở dòng trên, giờ chữ nhỏ ở dòng dưới. Không có giá trị thì hiện "-".
     Giờ 00:00:00 coi như không xác định được giờ (dữ liệu cũ) nên chỉ hiện ngày. --}}
@if($value)
    <div class="whitespace-nowrap">{{ $value->format('d/m/Y') }}</div>
    @if($time && $value->format('H:i:s') !== '00:00:00')
        <div class="text-xs text-slate-400">{{ $value->format('H:i') }}</div>
    @endif
@else
    -
@endif
