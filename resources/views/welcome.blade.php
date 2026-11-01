@php
    $site = $site ?? null;
    $siteName = $site?->title() ?? config('app.name', 'LNX');
    $siteTagline = $site?->tagline();
    $logoUrl = $site?->logoUrl() ?? asset(\App\Models\Setting::DEFAULT_LOGO);
    $logoDarkUrl = $site?->logoDarkUrl();
    $hasDarkLogo = filled($logoDarkUrl) && $logoDarkUrl !== $logoUrl;
    $registrationOpen = \App\Models\Setting::registrationIsOpen();
    $footerText = $site?->footer();

    $heroDescription = $site?->description()
        ?: __('A ready-to-use foundation to build your next application.');

    $showTagline = filled($siteTagline) && $siteTagline !== $heroDescription;

    $technologies = [
        ['name' => 'laravel', 'class' => 'h-5', 'label' => 'Laravel'],
        ['name' => 'livewire', 'class' => 'h-4', 'label' => 'Livewire'],
        ['name' => 'php', 'class' => 'h-3', 'label' => 'PHP'],
        ['name' => 'tailwindcss', 'class' => 'h-4', 'label' => 'Tailwind CSS'],
        ['name' => 'vite', 'class' => 'h-4', 'label' => 'Vite'],
        ['name' => 'pest', 'class' => 'h-4', 'label' => 'Pest'],
        ['name' => 'postgresql', 'class' => 'h-5 invert dark:invert-0', 'label' => 'PostgreSQL'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="relative flex h-svh flex-col overflow-hidden bg-white text-zinc-900 antialiased selection:bg-accent/20 dark:bg-zinc-950 dark:text-zinc-100">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_55%_at_50%_45%,color-mix(in_oklab,var(--color-accent)_18%,transparent),transparent_72%)] dark:bg-[radial-gradient(ellipse_60%_55%_at_50%_45%,color-mix(in_oklab,var(--color-accent)_26%,transparent),transparent_72%)]"></div>

            <div
                class="absolute inset-0 text-zinc-900 opacity-[0.035] [mask-image:radial-gradient(ellipse_55%_55%_at_50%_45%,black,transparent)] dark:text-white dark:opacity-[0.05]"
                style="background-image: linear-gradient(currentColor 1px, transparent 1px), linear-gradient(90deg, currentColor 1px, transparent 1px); background-size: 4rem 4rem;"
            ></div>

            <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-zinc-200 to-transparent dark:via-white/10"></div>
        </div>

        <main class="flex flex-1 flex-col items-center justify-center px-6 text-center" aria-labelledby="landing-title">
            <div class="flex size-16 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200/80 bg-white/80 shadow-xl shadow-accent/15 backdrop-blur-sm sm:size-20 dark:border-white/10 dark:bg-white/5">
                @if ($hasDarkLogo)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-10 rounded-xl object-contain sm:size-12 dark:hidden" />
                    <img src="{{ $logoDarkUrl }}" alt="{{ $siteName }}" class="hidden size-10 rounded-xl object-contain sm:size-12 dark:block" />
                @else
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-10 rounded-xl object-contain sm:size-12" />
                @endif
            </div>

            @if ($showTagline)
                <p class="mt-8 text-xs font-semibold tracking-[0.2em] text-accent-content uppercase">{{ $siteTagline }}</p>
            @endif

            <h1 id="landing-title" class="mt-4 max-w-4xl text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">{{ $siteName }}</h1>

            <p class="mt-5 max-w-md text-base/7 text-pretty text-zinc-600 dark:text-zinc-300">{{ $heroDescription }}</p>

            <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-full bg-[color-mix(in_oklab,var(--color-accent)_82%,black)] px-6 py-2.5 text-sm font-semibold text-accent-foreground shadow-lg shadow-accent/25 transition hover:bg-[color-mix(in_oklab,var(--color-accent)_76%,black)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                    >
                        {{ __('Go to dashboard') }}
                    </a>
                @else
                    @if (Route::has('register') && $registrationOpen)
                        <a
                            href="{{ route('register') }}"
                            class="inline-flex items-center rounded-full bg-[color-mix(in_oklab,var(--color-accent)_82%,black)] px-6 py-2.5 text-sm font-semibold text-accent-foreground shadow-lg shadow-accent/25 transition hover:bg-[color-mix(in_oklab,var(--color-accent)_76%,black)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        >
                            {{ __('Get started') }}
                        </a>

                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center rounded-full border border-zinc-300/80 bg-white/70 px-6 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:border-zinc-700 dark:bg-white/5 dark:text-zinc-200 dark:hover:bg-white/10"
                        >
                            {{ __('Log in') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center rounded-full bg-[color-mix(in_oklab,var(--color-accent)_82%,black)] px-6 py-2.5 text-sm font-semibold text-accent-foreground shadow-lg shadow-accent/25 transition hover:bg-[color-mix(in_oklab,var(--color-accent)_76%,black)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        >
                            {{ __('Log in') }}
                        </a>
                    @endif
                @endauth
            </div>
        </main>

        <footer class="flex flex-col items-center gap-4 px-6 pb-6 text-center sm:pb-8">
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 [@media(max-height:640px)]:hidden">
                <span class="text-[0.65rem] font-semibold tracking-[0.18em] text-zinc-400 uppercase dark:text-zinc-500">{{ __('Built with') }}</span>

                <ul class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                    @foreach ($technologies as $technology)
                        <li class="flex items-center opacity-60 transition-opacity duration-300 hover:opacity-100">
                            <x-brand-logo :name="$technology['name']" :class="$technology['class'].' w-auto'" />
                            <span class="sr-only">{{ $technology['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if (filled($footerText))
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $footerText }}</p>
            @endif

            <span class="inline-flex items-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
                {{ __('Coded with') }}
                <flux:icon.heart variant="mini" class="size-3.5 text-rose-500" aria-hidden="true" />
                {{ __('by') }}
                <a href="https://like.cl" target="_blank" rel="noopener noreferrer" class="font-medium text-zinc-500 underline-offset-2 hover:underline focus-visible:underline dark:text-zinc-400">{{ __('Like Innovación') }}</a>
            </span>
        </footer>
    </body>
</html>
