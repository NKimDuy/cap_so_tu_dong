@props(['type' => 'text'])
<input type="{{ $type }}" {{ $attributes->merge(['class' => 'w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500']) }}>
