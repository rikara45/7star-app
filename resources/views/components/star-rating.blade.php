@props([
    'rating',
    'max' => 7,
])

@php
    $filled = (int) filter_var($rating, FILTER_SANITIZE_NUMBER_INT);
@endphp

<div {{ $attributes->class(['flex justify-center mt-2 space-x-1 text-yellow-400 text-4xl']) }}>
    @for ($i = 1; $i <= $max; $i++)
        <span @class(['text-gray-200' => $i > $filled])>★</span>
    @endfor
</div>
