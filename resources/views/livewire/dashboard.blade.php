<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Welcome, :name', ['name' => $name])"
        :subtitle="__('Your work, your agent and your data in one place.')"
    >
        <x-slot:eyebrow>
            <time datetime="{{ $todayIso }}">{{ $today }}</time>
        </x-slot:eyebrow>
    </x-lnx-ui::page-header>

    @foreach ($sections as $group)
        @if ($group['widgets'] !== [])
            <section
                aria-labelledby="dashboard-{{ $group['section']->value }}-heading"
                class="space-y-4"
            >
                <flux:heading id="dashboard-{{ $group['section']->value }}-heading" level="2">
                    {{ $group['section']->label() }}
                </flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    @foreach ($group['widgets'] as $entry)
                        @include('livewire.dashboard.widget', $entry)
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</section>
