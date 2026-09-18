<?php

namespace App\Livewire;

use Illuminate\Support\Facades\App;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    public string $locale;

    /**
     * Mount the component with the active locale.
     */
    public function mount(): void
    {
        $this->locale = App::getLocale();
    }

    /**
     * Persist the selected locale and refresh the current page in real time.
     */
    public function setLocale(string $locale): void
    {
        if (! array_key_exists($locale, config('app.available_locales'))) {
            return;
        }

        session(['locale' => $locale]);

        App::setLocale($locale);

        $this->locale = $locale;

        $this->js('window.Livewire.navigate(window.location.href)');
    }
}
