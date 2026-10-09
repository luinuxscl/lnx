<?php

namespace App\Rules;

use App\Support\Navigation\NavigationIcon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts an empty icon (optional) or the name of a `flux:icon` that actually
 * exists. Prevents storing an unknown icon, which would make Flux throw while
 * rendering the navigation manager or the sidebar.
 */
class ExistsNavigationIcon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return;
        }

        if (! is_string($value) || ! NavigationIcon::exists($value)) {
            $fail(__('The selected icon does not exist.'));
        }
    }
}
