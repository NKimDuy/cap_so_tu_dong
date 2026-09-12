@props(['href', 'active' => false])
<a href="{{ $href }}"
   @class([
        'block px-3 py-2 rounded-lg transition',
        'bg-brand-600 text-white' => $active,
        'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $active,
   ])>
    {{ $slot }}
</a>
