@props(['type' => 'text'])
<input type="{{ $type }}" {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30']) }}>
