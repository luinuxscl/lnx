<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Support\Appearance as AppearanceSupport;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Appearance settings')]
class Appearance extends Component
{
    public string $theme;

    public string $colorScheme;

    public string $timezone;

    public string $locale;

    /**
     * Seed the form with the signed-in user's stored preferences.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->theme = in_array($user->theme, AppearanceSupport::THEMES, true)
            ? $user->theme
            : AppearanceSupport::defaultTheme();

        $this->colorScheme = in_array($user->color_scheme, AppearanceSupport::COLOR_SCHEMES, true)
            ? $user->color_scheme
            : AppearanceSupport::defaultColorScheme();

        $this->timezone = AppearanceSupport::currentTimezone($user);

        $this->locale = App::getLocale();
    }

    /**
     * Persist the selected accent theme.
     */
    public function saveTheme(string $theme): void
    {
        $validated = Validator::make(
            ['theme' => $theme],
            ['theme' => ['required', 'string', Rule::in(AppearanceSupport::THEMES)]],
        )->validate();

        $this->persist(['theme' => $validated['theme']]);

        $this->theme = $validated['theme'];
    }

    /**
     * Persist the selected color scheme.
     */
    public function saveColorScheme(string $colorScheme): void
    {
        $validated = Validator::make(
            ['color_scheme' => $colorScheme],
            ['color_scheme' => ['required', 'string', Rule::in(AppearanceSupport::COLOR_SCHEMES)]],
        )->validate();

        $this->persist(['color_scheme' => $validated['color_scheme']]);

        $this->colorScheme = $validated['color_scheme'];
    }

    /**
     * Persist the selected time zone when the bound property changes.
     */
    public function updatedTimezone(): void
    {
        $this->saveTimezone();
    }

    /**
     * Persist the selected time zone.
     */
    public function saveTimezone(): void
    {
        try {
            $validated = Validator::make(
                ['timezone' => $this->timezone],
                ['timezone' => ['required', 'string', 'timezone']],
            )->validate();
        } catch (ValidationException $exception) {
            $this->timezone = AppearanceSupport::currentTimezone(Auth::user());

            throw $exception;
        }

        $this->persist(['timezone' => $validated['timezone']]);

        $this->timezone = $validated['timezone'];
    }

    /**
     * Persist the selected locale when the bound property changes.
     */
    public function updatedLocale(): void
    {
        $this->saveLocale();
    }

    /**
     * Persist the selected locale and refresh the page in real time.
     */
    public function saveLocale(): void
    {
        try {
            $validated = Validator::make(
                ['locale' => $this->locale],
                ['locale' => ['required', 'string', Rule::in(array_keys((array) config('app.available_locales')))]],
            )->validate();
        } catch (ValidationException $exception) {
            $this->locale = App::getLocale();

            throw $exception;
        }

        $this->persist(['locale' => $validated['locale']]);

        session(['locale' => $validated['locale']]);

        App::setLocale($validated['locale']);

        $this->locale = $validated['locale'];

        $this->js('window.Livewire.navigate(window.location.href)');
    }

    public function render(): View
    {
        return view('livewire.settings.appearance', [
            'themes' => AppearanceSupport::THEMES,
            'timezones' => AppearanceSupport::timezones(),
        ])
            ->layout('layouts.app', [
                'breadcrumbs' => [
                    ['label' => __('Settings'), 'href' => route('profile.edit')],
                    ['label' => __('Appearance')],
                ],
            ]);
    }

    /**
     * Store the given preferences on the signed-in user.
     *
     * @param  array<string, string>  $attributes
     */
    protected function persist(array $attributes): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user->update($attributes);
        }
    }
}
