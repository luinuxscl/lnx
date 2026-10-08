@foreach ($groups as $group)
    @if ($group['label'] !== null)
        <flux:sidebar.group :heading="$group['label']" :icon="$group['icon']" expandable :expanded="collect($group['items'])->contains('current', true)">
            @foreach ($group['items'] as $item)
                @include('partials.navigation-sidebar-item', ['item' => $item])
            @endforeach
        </flux:sidebar.group>
    @else
        @foreach ($group['items'] as $item)
            @include('partials.navigation-sidebar-item', ['item' => $item])
        @endforeach
    @endif
@endforeach
