<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ __('Maintenance') }} - {{ ($site ?? null)?->title() ?? config('app.name', 'Laravel') }}</title>

        @if (($site ?? null)?->faviconUrl())
            <link rel="icon" href="{{ $site->faviconUrl() }}">
        @endif

        <style>
            :root {
                color-scheme: light dark;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                background-color: #fafafa;
                color: #18181b;
            }

            main {
                width: 100%;
                max-width: 28rem;
                text-align: center;
            }

            .logo {
                height: 2.5rem;
                width: auto;
                margin: 0 auto 1.5rem;
                display: block;
            }

            h1 {
                font-size: 1.375rem;
                font-weight: 600;
                margin: 0 0 0.75rem;
            }

            p {
                margin: 0;
                line-height: 1.6;
                color: #52525b;
            }

            .tagline {
                margin-top: 1.5rem;
                font-size: 0.875rem;
                color: #71717a;
            }

            @media (prefers-color-scheme: dark) {
                body {
                    background-color: #18181b;
                    color: #fafafa;
                }

                p {
                    color: #a1a1aa;
                }

                .tagline {
                    color: #71717a;
                }
            }
        </style>
    </head>
    <body>
        <main>
            @if (($site ?? null)?->logoUrl())
                <img src="{{ $site->logoDarkUrl() ?? $site->logoUrl() }}" alt="{{ $site->title() }}" class="logo" />
            @endif

            <h1>{{ __('We are performing scheduled maintenance.') }}</h1>

            <p>{{ $message }}</p>

            @if (filled(($site ?? null)?->site_tagline))
                <p class="tagline">{{ $site->site_tagline }}</p>
            @endif
        </main>
    </body>
</html>
