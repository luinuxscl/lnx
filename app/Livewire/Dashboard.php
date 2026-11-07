<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\Appearance;
use App\Support\Dashboard\DashboardRegistry;
use App\Support\Dashboard\DashboardSection;
use App\Support\Dashboard\DashboardWidget;
use App\Support\Dashboard\WidgetData;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Generic dashboard host.
 *
 * It knows nothing about the product: it asks the registry for the widgets,
 * filters them by the actor, resolves the typed data and renders the fixed
 * sections. Adding a widget never requires touching this class or its view.
 */
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        /** @var User $actor */
        $actor = auth()->user();
        $now = now(Appearance::currentTimezone($actor));

        $sections = [];

        foreach ([DashboardSection::Sam, DashboardSection::Stats, DashboardSection::Access] as $section) {
            $sections[] = [
                'section' => $section,
                'widgets' => $this->widgetsFor($actor, $section),
            ];
        }

        return view('livewire.dashboard', [
            'name' => $actor->name,
            'today' => $now->isoFormat('dddd, LL'),
            'todayIso' => $now->toDateString(),
            'sections' => $sections,
        ])->layout('layouts.app');
    }

    /**
     * Visible widgets of one section, already resolved and sorted.
     *
     * The permission check runs before `resolve()`, so a widget the actor
     * cannot see never executes its query.
     *
     * @return list<array{widget: DashboardWidget, data: WidgetData}>
     */
    private function widgetsFor(User $actor, DashboardSection $section): array
    {
        $widgets = [];

        foreach (app(DashboardRegistry::class)->all() as $widget) {
            if ($widget->section() !== $section || ! $widget->visible($actor)) {
                continue;
            }

            $data = $widget->resolve($actor);

            if ($data === null) {
                continue;
            }

            $widgets[] = ['widget' => $widget, 'data' => $data];
        }

        usort($widgets, static function (array $left, array $right): int {
            return [$left['widget']->order(), $left['widget']->id()]
                <=> [$right['widget']->order(), $right['widget']->id()];
        });

        return $widgets;
    }
}
