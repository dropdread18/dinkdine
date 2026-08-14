@props(['variant' => 'primary', 'tag' => 'button', 'color' => 'slate'])

@php
    $base = 'inline-flex items-center justify-center gap-2 text-sm font-medium transition-colors focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'rounded-lg px-4 py-2.5 bg-teal-600 text-white shadow-sm hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2',
        'secondary' => 'rounded-lg px-4 py-2.5 bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2',
        'danger' => 'rounded-lg px-4 py-2.5 bg-white text-red-600 border border-red-200 hover:bg-red-50 focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
        'ghost' => match ($color) {
            'red' => 'text-red-600 hover:text-red-700 underline underline-offset-2',
            'green' => 'text-emerald-600 hover:text-emerald-700 underline underline-offset-2',
            default => 'text-slate-600 hover:text-slate-900 underline underline-offset-2',
        },
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($tag === 'a')
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
