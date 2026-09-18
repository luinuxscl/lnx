# lnx — Base de desarrollo Laravel

Base de desarrollo personalizada construida sobre el **starter kit oficial de Laravel (Livewire)**. Incluye autenticación completa (Fortify + Passkeys + 2FA), settings y un sistema de theming configurable.

## Stack

| Capa          | Tecnología                                             |
|---------------|--------------------------------------------------------|
| Framework     | Laravel 13 (PHP 8.5)                                   |
| Frontend      | Livewire 4 + Alpine.js + Tailwind CSS 4                |
| UI            | Flux UI (free edition) + Heroicons                     |
| AUTH          | Laravel Fortify (login, 2FA, registro, verificación)   |
| Passkeys      | Laravel Passkeys (soporte WebAuthn)                    |
| Tests         | Pest                                                  |
| Base de datos  | SQLite por defecto                                     |
| Idiomas       | Español (por defecto) + Inglés                         |

## Requisitos

- PHP >= 8.5
- Composer
- Node.js (para Vite)

## Instalación

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

## Comandos útiles

| Comando                 | Descripción                          |
|-------------------------|--------------------------------------|
| `npm run dev`           | Dev server de Vite (hot reload)      |
| `npm run build`         | Compila assets de producción         |
| `php artisan serve`     | Servidor local (puerto 8000)         |
| `php artisan test`      | Ejecuta la suite de Pest             |
| `vendor/bin/pint`       | Formatea el código PHP               |
| `vendor/bin/phpstan`    | Análisis estático                    |

## Theming

La aplicación soporta selección de tema (color de acento) y modo claro/oscuro/sistema desde **Settings → Appearance**:

- **Colores de acento**: Naranja (por defecto), Teal, Rosa, Violeta, Azul, Esmeralda.
- **Modo**: Light, Dark, System (persistido por navegador en `localStorage`).

### Cómo funciona

- El gris base de Flux (`zinc`) ya está reasignado a la paleta `neutral` del proyecto en `resources/css/app.css`.
- Cada tema se define como una clase `.theme-{color}` en `resources/css/app.css`, que sobreescribe las variables CSS `--color-accent`, `--color-accent-content` y `--color-accent-foreground` (con variantes `.dark` por tema).
- Al cargar la página, `resources/views/partials/head.blade.php` aplica la clase `theme-*` desde `localStorage['flux.theme']` antes del render para evitar flashes.
- El modo claro/oscuro lo gestiona Flux vía `localStorage['flux.appearance']`.
- Tras cada navegación SPA (`wire:navigate`), el tema y el modo claro/oscuro se re-aplican al escuchar `livewire:navigated` en `resources/views/partials/head.blade.php`. Esto evita que el servidor (que renderiza `class="dark"`) elimine las clases `theme-{color}` cuando sustituye los atributos del elemento `<html>` durante la navegación.

Para añadir un nuevo color de acento: agrega la entrada en el arreglo `themes` de `resources/views/livewire/settings/appearance.blade.php` y define `.theme-{color}` (+ `.dark.theme-{color}`) en `resources/css/app.css`.

## Localización (i18n)

La aplicación es bilingüe: **español (por defecto)** e **inglés**. El idioma se guarda en la sesión y se aplica en cada request mediante `App\Http\Middleware\SetLocale`.

### Configuración

- `.env`: `APP_LOCALE=es`, `APP_FALLBACK_LOCALE=es`.
- `config/app.php`: `available_locales` define los idiomas soportados y su etiqueta en el selector:
  ```php
  'available_locales' => [
      'es' => 'Español',
      'en' => 'English',
  ],
  ```

### Cadenas de traducción

Se usa la estrategia de Laravel de **"translation strings as keys"**: la clave es el texto fuente en inglés y las traducciones viven en archivos JSON.

- `lang/es.json` — traducción al español.
- `lang/en.json` — inglés (clave = valor).
- `lang/{es,en}/{auth,passwords,pagination,validation}.php` — mensajes de autenticación y validación.
- En las vistas se usa `{{ __('Dashboard') }}`, `{{ __('Log in') }}`, etc.

### Cambiar de idioma en tiempo real

- **Vistas con Livewire** (app, settings, auth): componente `App\Livewire\LocaleSwitcher` (`resources/views/livewire/locale-switcher.blade.php`), mostrado en el header de contenido en desktop y en el header móvil. Al elegir un idioma guarda la sesión y navega con `window.Livewire.navigate(window.location.href)`, por lo que la página se re-renderiza al instante sin recarga completa.
- **Página de bienvenida** (sin Livewire): formulario `POST route('locale.update')` (`LocaleController`) con un `<select>` que se autoenvía al cambiar.

### Regla de trabajo

Toda vista nueva o modificada debe crearse **siempre en ambos idiomas**: envuelve los textos en `__()` y añade la clave con su traducción en `lang/es.json` y `lang/en.json`. Los títulos de página pasan por `__($title)` en `resources/views/partials/head.blade.php`, por lo que `#[Title('Profile settings')]` también se traduce.

## Estrategia de ramas (Git Flow)

- `main` — producción, siempre estable.
- `develop` — integración; rama base de trabajo.
- `feature/{nombre}` — nuevos features, creadas desde `develop`.
- `fix/{nombre}` / `hotfix/{nombre}` — correcciones, creadas desde `main`.

**El workflow de documentación es obligatorio**: cada feature o corrección debe incluir la actualización de la documentación afectada antes de integrarse.

## Documentación

- `README.md` — este archivo, panorámica del proyecto.
- `.ai/rules/` — reglas permanentes del proyecto (git flow, documentación).
- `.agents/skills/` — skills de dominio locales (Flux, Livewire, Fortify, testing, etc.).
- `.kilo/skills/consolidar/` — skill de cierre: al decir "consolidar" ejecuta documentación, commits, merge y push tras una confirmación simple.

## Estructura clave

```
app/Livewire/Settings/            Componentes de settings
resources/views/layouts/          Layouts (app, auth)
resources/css/app.css             Tokens de Tailwind + theming Flux
.kilo/agent/                      Sub-agentes de Kilo (ui-audit: auditoría visual de vistas con modelo económico de visión)
.kilo/skills/                     Skills de Kilo versionadas (consolidar: documentación + commits + merge + push)
tests/                            Suite de Pest
```