@props(['status'])
@php
    $color = $status?->badgeColor() ?? 'slate';
    $classes = [
        'amber' => 'bg-amber-100 text-amber-800',
        'green' => 'bg-green-100 text-green-800',
        'slate' => 'bg-slate-100 text-slate-700',
        'red' => 'bg-red-100 text-red-800',
    ][$color] ?? 'bg-slate-100 text-slate-700';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-medium $classes"]) }}>
    {{ $status?->label() }}
</span>
