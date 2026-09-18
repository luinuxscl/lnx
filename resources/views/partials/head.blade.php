<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
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

        document.addEventListener('livewire:navigated', () => {
            applyFluxTheme();
            applyFluxAppearance();
        });
    })();
</script>
