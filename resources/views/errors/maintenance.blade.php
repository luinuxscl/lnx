<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ __('Maintenance') }} - {{ ($site ?? null)?->title() ?? config('app.name', 'LNX') }}</title>

        <link rel="icon" href="{{ ($site ?? null)?->faviconUrl() ?? asset(\App\Models\Setting::DEFAULT_FAVICON) }}">

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
                /* Hexes zinc de la rampa reasignada en app.css (zinc apunta a neutral):
                    mantenerlos sincronizados con el resto de la app (crm#16). */
                background-color: #fafafa;
                color: #171717;
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
                color: #525252;
            }

            .tagline {
                margin-top: 1.5rem;
                font-size: 0.875rem;
                color: #737373;
            }

            /* El logo claro/oscuro sigue el esquema del sistema: sin JS ni assets,
                no hay toggle de tema; se empareja como en welcome (crm#16). */
            .logo-dark {
                display: none;
            }

            @media (prefers-color-scheme: dark) {
                body {
                    background-color: #171717;
                    color: #fafafa;
                }

                p {
                    color: #a3a3a3;
                }

                .tagline {
                    color: #737373;
                }

                .logo-light {
                    display: none;
                }

                .logo-dark {
                    display: block;
                }
            }
        </style>
    </head>
    <body>
        <main>
            @if (($site ?? null)?->logoUrl())
                @if ($site->logoDarkUrl() && $site->logoDarkUrl() !== $site->logoUrl())
                    {{-- Par claro/oscuro como en welcome: el logo oscuro sobre fondo claro
                        desaparece; cada esquema recibe su variante (crm#16). --}}
                    <img src="{{ $site->logoUrl() }}" alt="{{ $site->title() }}" class="logo logo-light" />
                    <img src="{{ $site->logoDarkUrl() }}" alt="{{ $site->title() }}" class="logo logo-dark" />
                @else
                    <img src="{{ $site->logoUrl() }}" alt="{{ $site->title() }}" class="logo" />
                @endif
            @endif

            <h1>{{ __('We are performing scheduled maintenance.') }}</h1>

            <p>{{ $message }}</p>

            @if (filled(($site ?? null)?->tagline()))
                <p class="tagline">{{ $site->tagline() }}</p>
            @endif
        </main>
    </body>
</html>
