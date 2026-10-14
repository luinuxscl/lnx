@php
    $site = $site ?? null;
    $siteName = $site?->title() ?? config('app.name', 'LNX');
    $siteTagline = $site?->tagline();
    $logoUrl = $site?->logoUrl() ?? asset(\App\Models\Setting::DEFAULT_LOGO);
    $logoDarkUrl = $site?->logoDarkUrl();
    $hasDarkLogo = filled($logoDarkUrl) && $logoDarkUrl !== $logoUrl;
    $registrationOpen = \App\Models\Setting::registrationIsOpen();

    $heroDescription = $site?->description()
        ?: __('A ready-to-use foundation to build your next application.');

    $features = [
        [
            'icon' => 'shield-check',
            'title' => __('Secure by default'),
            'description' => __('Passkeys, two-factor authentication and email verification are ready from day one.'),
        ],
        [
            'icon' => 'users',
            'title' => __('Roles and permissions'),
            'description' => __('Role-based access control with a clear separation between administrators and users.'),
        ],
        [
            'icon' => 'sparkles',
            'title' => __('AI-powered tools'),
            'description' => __('Chat, retrieval and document AI integrations you can enable whenever you need them.'),
        ],
        [
            'icon' => 'bars-3',
            'title' => __('Managed navigation'),
            'description' => __('Shape the sidebar for each audience without touching a single line of code.'),
        ],
        [
            'icon' => 'language',
            'title' => __('Bilingual'),
            'description' => __('Spanish and English out of the box, with language preferences per account.'),
        ],
        [
            'icon' => 'paint-brush',
            'title' => __('Your brand'),
            'description' => __('Bring your own logo, icons and accent color from the settings panel.'),
        ],
    ];

    $technologies = [
        ['name' => 'laravel', 'class' => 'h-9', 'label' => 'Laravel'],
        ['name' => 'livewire', 'class' => 'h-8', 'label' => 'Livewire'],
        ['name' => 'php', 'class' => 'h-6', 'label' => 'PHP'],
        ['name' => 'tailwindcss', 'class' => 'h-7', 'label' => 'Tailwind CSS'],
        ['name' => 'vite', 'class' => 'h-8', 'label' => 'Vite'],
        ['name' => 'pest', 'class' => 'h-7', 'label' => 'Pest'],
        ['name' => 'postgresql', 'class' => 'h-9', 'label' => 'PostgreSQL'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <header class="sticky top-0 z-40 border-b border-zinc-200/70 bg-white/80 backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-950/80">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent" aria-label="{{ $siteName }}">
                    <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md">
                        @if ($hasDarkLogo)
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-9 rounded-md object-contain dark:hidden" />
                            <img src="{{ $logoDarkUrl }}" alt="{{ $siteName }}" class="hidden size-9 rounded-md object-contain dark:block" />
                        @else
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-9 rounded-md object-contain" />
                        @endif
                    </span>

                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-sm leading-5 font-bold">{{ $siteName }}</span>
                        @if (filled($siteTagline))
                            <span class="truncate text-xs leading-4 font-medium text-zinc-500 dark:text-zinc-400">{{ $siteTagline }}</span>
                        @endif
                    </span>
                </a>

                <nav class="flex items-center gap-2 sm:gap-3" aria-label="{{ __('Main navigation') }}">
                    <form method="POST" action="{{ route('locale.update') }}">
                        @csrf
                        <label for="locale" class="sr-only">{{ __('Language') }}</label>
                        <select
                            id="locale"
                            name="locale"
                            aria-label="{{ __('Language') }}"
                            onchange="this.form.submit()"
                            class="cursor-pointer rounded-md border border-zinc-300 bg-transparent px-2 py-1.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:border-zinc-700"
                        >
                            @foreach (config('app.available_locales') as $code => $label)
                                <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>

                    <button
                        type="button"
                        class="inline-flex size-9 items-center justify-center rounded-md text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:text-zinc-300 dark:hover:bg-zinc-800"
                        aria-label="{{ __('Toggle dark mode') }}"
                        onclick="window.Flux.applyAppearance(document.documentElement.classList.contains('dark') ? 'light' : 'dark');"
                    >
                        <flux:icon.sun class="size-5 dark:hidden" />
                        <flux:icon.moon class="hidden size-5 dark:block" />
                    </button>

                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex items-center rounded-md bg-accent px-4 py-1.5 text-sm font-medium text-accent-foreground hover:bg-accent/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        >
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="hidden items-center rounded-md px-4 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:inline-flex dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            {{ __('Log in') }}
                        </a>

                        @if (Route::has('register') && $registrationOpen)
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center rounded-md bg-accent px-4 py-1.5 text-sm font-medium text-accent-foreground hover:bg-accent/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                            >
                                {{ __('Register') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6 sm:py-24 lg:px-8">
                <p class="text-sm font-semibold tracking-wide text-accent-content uppercase">{{ __('Welcome') }}</p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">{{ $siteName }}</h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg text-pretty text-zinc-600 dark:text-zinc-300">{{ $heroDescription }}</p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex items-center rounded-md bg-accent px-6 py-2.5 text-sm font-semibold text-accent-foreground hover:bg-accent/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        >
                            {{ __('Go to dashboard') }}
                        </a>
                    @else
                        @if (Route::has('register') && $registrationOpen)
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center rounded-md bg-accent px-6 py-2.5 text-sm font-semibold text-accent-foreground hover:bg-accent/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                            >
                                {{ __('Get started') }}
                            </a>
                        @endif

                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center rounded-md border border-zinc-300 px-6 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            {{ __('Log in') }}
                        </a>
                    @endauth
                </div>
            </section>

            <section class="border-t border-zinc-200/70 py-12 dark:border-zinc-800/70">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Built with') }}</p>

                    <ul class="mt-8 flex flex-wrap items-center justify-center gap-x-10 gap-y-6">
                        @foreach ($technologies as $technology)
                            <li class="flex items-center text-zinc-800 dark:text-zinc-100">
                                <x-brand-logo :name="$technology['name']" :class="$technology['class'].' w-auto'" />
                                <span class="sr-only">{{ $technology['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="border-t border-zinc-200/70 bg-white/60 py-16 sm:py-20 dark:border-zinc-800/70 dark:bg-zinc-900/40">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-2xl font-bold tracking-tight text-balance sm:text-3xl">{{ __('Everything you need to get started') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-300">{{ __('A solid base with the essentials already wired up.') }}</p>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($features as $feature)
                            <article class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <span class="inline-flex size-11 items-center justify-center rounded-lg bg-accent-content/10 text-accent-content">
                                    <flux:icon :name="$feature['icon']" class="size-6" />
                                </span>
                                <h3 class="mt-4 text-base font-semibold">{{ $feature['title'] }}</h3>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $feature['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <div class="rounded-3xl bg-accent px-6 py-14 text-center text-accent-foreground sm:px-12">
                    <h2 class="text-2xl font-bold tracking-tight text-balance sm:text-3xl">{{ __('Ready to get started?') }}</h2>
                    <p class="mx-auto mt-4 max-w-xl text-pretty opacity-90">{{ __('Create your account and start building right away.') }}</p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center rounded-md bg-accent-foreground px-6 py-2.5 text-sm font-semibold text-accent hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-foreground"
                            >
                                {{ __('Go to dashboard') }}
                            </a>
                        @else
                            @if (Route::has('register') && $registrationOpen)
                                <a
                                    href="{{ route('register') }}"
                                    class="inline-flex items-center rounded-md bg-accent-foreground px-6 py-2.5 text-sm font-semibold text-accent hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-foreground"
                                >
                                    {{ __('Get started') }}
                                </a>
                            @endif

                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center rounded-md border border-current px-6 py-2.5 text-sm font-semibold hover:bg-accent-foreground/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-foreground"
                            >
                                {{ __('Log in') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-zinc-200/70 py-8 dark:border-zinc-800/70">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 text-sm text-zinc-500 sm:flex-row sm:px-6 lg:px-8 dark:text-zinc-400">
                @if (filled($site?->footer()))
                    <span>{{ $site->footer() }}</span>
                @endif

                <span class="inline-flex items-center gap-1">
                    {{ __('Coded with') }}
                    <flux:icon.heart variant="mini" class="size-4 text-rose-500" aria-hidden="true" />
                    {{ __('by') }}
                    <a href="https://like.cl" target="_blank" rel="noopener noreferrer" class="font-medium text-zinc-600 underline-offset-2 hover:underline focus-visible:underline dark:text-zinc-300">{{ __('Like Innovación') }}</a>
                </span>
            </div>
        </footer>
    </body>
</html>
