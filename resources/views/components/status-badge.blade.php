@props([
    'tone' => 'gray',
    'size' => 'sm',
    'subtle' => false,
    'pulse' => false,
])

@php
    $palette = [
        'green' => $subtle ? 'bg-green-50 text-green-700 border-green-100' : 'bg-green-200 text-green-800 border-green-200',
        'blue' => $subtle ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-blue-200 text-blue-800 border-blue-200',
        'yellow' => $subtle ? 'bg-yellow-50 text-yellow-700 border-yellow-100' : 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'gray' => $subtle ? 'bg-gray-100 text-gray-500 border-transparent' : 'bg-gray-100 text-gray-400 border-gray-200',
        'red' => $subtle ? 'bg-red-50 text-red-700 border-red-100' : 'bg-red-100 text-red-800 border-red-200',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-full border font-bold px-2 py-1',
    'text-xs' => $size === 'sm',
    'text-[10px]' => $size === 'xs',
    'animate-pulse' => $pulse,
    $palette[$tone] ?? $palette['gray'],
]) }}>{{ $slot }}</span>
