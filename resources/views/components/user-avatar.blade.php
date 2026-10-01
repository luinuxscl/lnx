@props(['user', 'size' => null])

@php
    $uploadedUrl = $user->uploadedAvatarUrl();
    $gravatarUrl = $user->gravatarUrl();
    $sizeClass = match ($size) {
        'sm' => 'size-8',
        'lg' => 'size-12',
        default => 'size-10',
    };
    $radiusClass = match ($size) {
        'sm' => 'rounded-md',
        'lg' => 'rounded-xl',
        default => 'rounded-lg',
    };
@endphp

<span
    {{ $attributes->class("relative inline-block shrink-0 {$sizeClass}") }}
    x-data="{
        source: @js($uploadedUrl ?? $gravatarUrl),
        gravatar: @js($gravatarUrl),
        failed: false,
        fallback() { if (this.source !== this.gravatar) { this.source = this.gravatar } else { this.failed = true } }
    }"
    x-init="if ($refs.image.complete && !$refs.image.naturalWidth) fallback()"
>
    <flux:avatar :name="$user->name" :initials="$user->initials()" :size="$size" />

    <img
        src="{{ $uploadedUrl ?? $gravatarUrl }}"
        x-ref="image"
        x-bind:src="source"
        x-show="!failed"
        x-on:error="fallback()"
        alt="{{ $user->name }}"
        class="absolute inset-0 size-full {{ $radiusClass }} object-cover"
    />
</span>
