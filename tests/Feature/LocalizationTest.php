<?php

use App\Livewire\LocaleSwitcher;
use Livewire\Livewire;

it('uses spanish as the default locale', function () {
    expect(config('app.locale'))->toBe('es')
        ->and(config('app.fallback_locale'))->toBe('es')
        ->and(config('app.available_locales'))->toHaveKeys(['es', 'en']);
});

it('translates the home page into spanish by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Bienvenido')
        ->assertSee('Iniciar sesión');
});

it('stores the selected locale in the session', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('locale', 'en');
});

it('translates subsequent requests into the selected locale', function () {
    $this->withSession(['locale' => 'en'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome')
        ->assertSee('Log in');
});

it('falls back to the default locale for unsupported session values', function () {
    $this->withSession(['locale' => 'fr'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Bienvenido');
});

it('rejects unsupported locales', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

it('switches the locale through the livewire switcher', function () {
    Livewire::test(LocaleSwitcher::class)
        ->assertSet('locale', 'es')
        ->call('setLocale', 'en')
        ->assertSet('locale', 'en');

    expect(session('locale'))->toBe('en');
});

it('ignores unsupported locales in the livewire switcher', function () {
    Livewire::test(LocaleSwitcher::class)
        ->call('setLocale', 'fr')
        ->assertSet('locale', 'es');

    expect(session('locale'))->toBeNull();
});

it('has json translations for every supported locale', function () {
    foreach (array_keys(config('app.available_locales')) as $locale) {
        expect(file_exists(lang_path("{$locale}.json")))->toBeTrue();
    }
});

it('translates validation messages into spanish', function () {
    expect(__('validation.required', ['attribute' => 'email']))->toBe('El campo email es obligatorio.');
});
