<?php

namespace App\Support\Dashboard;

use InvalidArgumentException;

/**
 * In-memory registry of the widgets installed packages declare.
 *
 * A package registers its widgets during boot; the kit never exposes them just
 * because the package sits in `vendor/`. Declarations are validated, a
 * duplicate id inside one declaration is an error, and registering the same
 * source twice replaces its widgets so a double boot stays idempotent.
 */
class DashboardRegistry
{
    /**
     * Registered widgets, keyed by source and then by widget id.
     *
     * @var array<string, array<string, DashboardWidget>>
     */
    private array $widgets = [];

    /**
     * Declare the widgets a package contributes to the dashboard.
     *
     * @param  array<int, class-string<DashboardWidget>>  $widgets
     *
     * @throws InvalidArgumentException when the source is empty, an id is not
     *                                  prefixed by it, the id is declared twice
     *                                  or the span is out of range
     */
    public function register(string $source, array $widgets): void
    {
        $source = trim($source);

        if ($source === '') {
            throw new InvalidArgumentException('The dashboard source must be a non-empty string.');
        }

        $registered = [];

        foreach ($widgets as $widget) {
            $instance = $this->resolve($widget);
            $id = trim($instance->id());

            if ($id === '') {
                throw new InvalidArgumentException("A widget declared by [{$source}] has an empty id.");
            }

            if (! str_starts_with($id, $source.'.')) {
                throw new InvalidArgumentException(
                    "The widget id [{$id}] must be prefixed with the package source [{$source}.]."
                );
            }

            if ($instance->source() !== $source) {
                throw new InvalidArgumentException(
                    "The widget [{$id}] declares source [{$instance->source()}] but was registered as [{$source}]."
                );
            }

            if (isset($registered[$id])) {
                throw new InvalidArgumentException("The widget id [{$id}] is declared twice by [{$source}].");
            }

            if ($instance->span() < 1 || $instance->span() > 12) {
                throw new InvalidArgumentException("The widget [{$id}] must span between 1 and 12 columns.");
            }

            $registered[$id] = $instance;
        }

        $this->widgets[$source] = $registered;
    }

    /**
     * Every registered widget, in registration order.
     *
     * @return list<DashboardWidget>
     */
    public function all(): array
    {
        $widgets = [];

        foreach ($this->widgets as $registered) {
            foreach ($registered as $widget) {
                $widgets[] = $widget;
            }
        }

        return $widgets;
    }

    /**
     * @param  class-string<DashboardWidget>  $widget
     */
    private function resolve(string $widget): DashboardWidget
    {
        if (! is_a($widget, DashboardWidget::class, true)) {
            throw new InvalidArgumentException(
                "The class [{$widget}] must implement ".DashboardWidget::class.'.'
            );
        }

        return app($widget);
    }
}
