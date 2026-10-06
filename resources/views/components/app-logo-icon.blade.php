<img
    src="{{ asset(\App\Models\Setting::DEFAULT_LOGO) }}"
    alt="{{ config('app.name', 'LNX') }}"
    {{ $attributes->merge(['class' => 'object-contain']) }}
/>
