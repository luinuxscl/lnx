<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
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
     *
     * Authenticated users also store the choice on their account so it follows
     * them across devices; guests keep the session-only behavior.
     */
    public function setLocale(string $locale): void
    {
        if (! array_key_exists($locale, config('app.available_locales'))) {
            return;
        }

        session(['locale' => $locale]);

        $user = Auth::user();

        if ($user instanceof User) {
            $user->update(['locale' => $locale]);
        }

        App::setLocale($locale);

        $this->locale = $locale;

        $this->js('window.Livewire.navigate(window.location.href)');
    }
}
