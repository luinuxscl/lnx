<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    @php
        $siteName = ($site ?? null)?->title() ?? config('app.name', 'LNX');
        $pageTitle = filled($title ?? null) ? __($title) : null;
        $metaDescription = ($site ?? null)?->description();
    @endphp

    {{ filled($pageTitle) ? $pageTitle.' - '.$siteName : $siteName }}
</title>

@if (filled($metaDescription))
    <meta name="description" content="{{ $metaDescription }}">
@endif

<link rel="icon" href="{{ ($site ?? null)?->faviconUrl() ?? asset(\App\Models\Setting::DEFAULT_FAVICON) }}">

<link rel="apple-touch-icon" href="{{ ($site ?? null)?->appleTouchIconUrl() ?? asset(\App\Models\Setting::DEFAULT_APPLE_TOUCH_ICON) }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

@php
    $siteTheme = ($site ?? null)?->accentTheme() ?? \App\Support\Appearance::defaultTheme();
@endphp

<script>
    window.localStorage.setItem('flux.theme', @json($siteTheme));
</script>

@auth
    @php
        $accountColorScheme = auth()->user()->color_scheme;
    @endphp

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
