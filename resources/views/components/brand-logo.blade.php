@props(['name'])

<x-dynamic-component :component="'brand-logos.'.$name" {{ $attributes }} />
