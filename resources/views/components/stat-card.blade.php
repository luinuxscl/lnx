@props([
    'label',
    'value',
    'icon',
    'color' => 'accent',
])

<flux:card {{ $attributes->class('h-full') }}>
    <x-stat :$label :$value :$icon :$color />
</flux:card>
