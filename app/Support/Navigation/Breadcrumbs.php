<?php

namespace App\Support\Navigation;

use Illuminate\Support\Facades\Route;

/**
 * Resolves the breadcrumb chain rendered by the `layouts.app` header.
 *
 * The section root (the top-level context of the current page) is centralized
 * here, so no page renders without breadcrumbs and no component has to declare
 * it. A component may still declare intermediate ancestors (items with an
 * `href`) and, for pages whose title is dynamic or missing, the current page
 * label (a trailing item without an `href`).
 */
class Breadcrumbs
{
    /**
     * Route-name prefix => section root label (translation key) and route.
     *
     * Prefixes ending in a dot match any route under that namespace; the rest
     * match the exact route name. More specific prefixes are listed first.
     *
     * @var array<string, array{label: string, route: string}>
     */
    private const SECTIONS = [
        'dashboard' => ['label' => 'Dashboard', 'route' => 'dashboard'],
        'help' => ['label' => 'Help center', 'route' => 'help.index'],
        'admin.users.' => ['label' => 'Users', 'route' => 'admin.users.index'],
        'admin.settings.' => ['label' => 'Settings', 'route' => 'admin.settings.edit'],
        'settings.' => ['label' => 'Settings', 'route' => 'profile.edit'],
        'knowledge.' => ['label' => 'Knowledge Base', 'route' => 'knowledge.index'],
        'documents.' => ['label' => 'Document extraction', 'route' => 'documents.index'],
        'support.' => ['label' => 'Support requests', 'route' => 'support.index'],
        'admin.activity.' => ['label' => 'Administrative activity', 'route' => 'admin.activity.index'],
        'admin.setup.' => ['label' => 'Setup checklist', 'route' => 'admin.setup.index'],
        'admin.ai.' => ['label' => 'AI', 'route' => 'admin.ai.index'],
        'admin.packages.' => ['label' => 'Packages', 'route' => 'admin.packages.index'],
        'admin.navigation.' => ['label' => 'Navigation', 'route' => 'admin.navigation.index'],
        'admin.emails.' => ['label' => 'Sent emails', 'route' => 'admin.emails.index'],
        'notifications.' => ['label' => 'Notifications', 'route' => 'notifications.index'],
        'chat' => ['label' => 'Chat', 'route' => 'chat'],
    ];

    /**
     * Build the full breadcrumb chain for the current page.
     *
     * @param  array<int, array{label?: string, href?: string|null}>|null  $declared
     * @return array<int, array{label: string, href: string|null}>
     */
    public function resolve(?array $declared = null, ?string $title = null): array
    {
        $declared = array_values(array_filter(
            $declared ?? [],
            fn (array $item): bool => filled($item['label'] ?? null),
        ));

        $ancestors = array_values(array_filter(
            $declared,
            fn (array $item): bool => filled($item['href'] ?? null),
        ));

        $current = $this->declaredCurrent($declared);

        $routeName = Route::current()?->getName();
        $section = $this->sectionFor($routeName);

        $items = [];

        if ($section !== null) {
            $isSectionRoot = $routeName === $section['route'];

            $items[] = [
                'label' => (string) __($section['label']),
                'href' => $isSectionRoot ? null : route($section['route']),
            ];

            // A section index page is its own root: collapse to a single item.
            if ($isSectionRoot) {
                return $this->normalize($items, $section['label']);
            }
        }

        foreach ($ancestors as $ancestor) {
            $items[] = [
                'label' => (string) __($ancestor['label']),
                'href' => $ancestor['href'],
            ];
        }

        $currentLabel = $current
            ?? (filled($title) ? (string) __($title) : null)
            ?? ($section !== null ? (string) __($section['label']) : null);

        if ($currentLabel !== null) {
            $items[] = ['label' => $currentLabel, 'href' => null];
        }

        return $this->normalize($items, $title);
    }

    /**
     * The section root declared for a route name, if any.
     *
     * @return array{label: string, route: string}|null
     */
    public function sectionFor(?string $routeName): ?array
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::SECTIONS as $prefix => $section) {
            $matches = str_ends_with($prefix, '.')
                ? str_starts_with($routeName, $prefix)
                : $routeName === $prefix;

            if ($matches && Route::has($section['route'])) {
                return $section;
            }
        }

        return null;
    }

    /**
     * The current page label when the last declared item is not a link.
     *
     * @param  array<int, array{label?: string, href?: string|null}>  $declared
     */
    private function declaredCurrent(array $declared): ?string
    {
        if ($declared === []) {
            return null;
        }

        $last = $declared[array_key_last($declared)];

        if (filled($last['href'] ?? null)) {
            return null;
        }

        return (string) __($last['label'] ?? '');
    }

    /**
     * Drop empty or repeated labels and never link the last item.
     *
     * @param  array<int, array{label: string, href: string|null}>  $items
     * @return array<int, array{label: string, href: string|null}>
     */
    private function normalize(array $items, ?string $title): array
    {
        $result = [];

        foreach ($items as $item) {
            $label = trim($item['label']);

            if ($label === '' || ($result !== [] && $result[array_key_last($result)]['label'] === $label)) {
                continue;
            }

            $result[] = ['label' => $label, 'href' => $item['href']];
        }

        if ($result === [] && filled($title)) {
            $result[] = ['label' => (string) __($title), 'href' => null];
        }

        if ($result !== []) {
            $last = count($result) - 1;
            $result[$last] = ['label' => $result[$last]['label'], 'href' => null];
        }

        return $result;
    }
}
