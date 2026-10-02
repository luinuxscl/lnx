<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    @php
        $siteName = ($site ?? null)?->title() ?? config('app.name', 'Laravel');
        $pageTitle = filled($title ?? null) ? __($title) : null;
        $metaDescription = ($site ?? null)?->description();
    @endphp

    {{ filled($pageTitle) ? $pageTitle.' - '.$siteName : $siteName }}
</title>

@if (filled($metaDescription))
    <meta name="description" content="{{ $metaDescription }}">
@endif

@if (($site ?? null)?->faviconUrl())
    <link rel="icon" href="{{ $site->faviconUrl() }}">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
@endif

@if (($site ?? null)?->appleTouchIconUrl())
    <link rel="apple-touch-icon" href="{{ $site->appleTouchIconUrl() }}">
@else
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

@auth
    @php
        $accountTheme = auth()->user()->theme;
        $accountColorScheme = auth()->user()->color_scheme;
    @endphp

    @if (in_array($accountTheme, \App\Support\Appearance::THEMES, true))
        <script>
            window.localStorage.setItem('flux.theme', @json($accountTheme));
        </script>
    @endif

    @if (in_array($accountColorScheme, \App\Support\Appearance::COLOR_SCHEMES, true))
        <script>
            window.localStorage.setItem('flux.appearance', @json($accountColorScheme));
        </script>
    @endif
@endauth

@fluxAppearance

<script>
    (() => {
        const applyFluxTheme = () => {
            const theme = window.localStorage.getItem('flux.theme') ?? 'orange';

            document.documentElement.classList.remove(...Array.from(document.documentElement.classList).filter((name) => name.startsWith('theme-')));

            document.documentElement.classList.add('theme-'.concat(theme));
        };

        const applyFluxAppearance = () => {
            window.Flux?.applyAppearance?.(window.localStorage.getItem('flux.appearance') || 'system');
        };

        window.applyFluxTheme = applyFluxTheme;
        window.applyFluxAppearance = applyFluxAppearance;

        applyFluxTheme();
        applyFluxAppearance();

        if (window.localStorage.getItem('flux-sidebar-collapsed-desktop') === null) {
            window.localStorage.setItem('flux-sidebar-collapsed-desktop', 'true');
        }

        document.addEventListener('livewire:navigated', () => {
            applyFluxTheme();
            applyFluxAppearance();
        });
    })();
</script>

@if (filled(($site ?? null)?->analytics_id) && app()->isProduction())
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $site->analytics_id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($site->analytics_id));
    </script>
@endif
