# Sidebar colapsable + header de contenido con breadcrumb y selector de idioma

## Objetivo

1. Sidebar colapsable en desktop (modo iconos), **colapsado por defecto**, con estado persistido entre recargas.
2. Nuevo header en el área de contenido (solo desktop, `lg+`):
   - Izquierda: botón para colapsar/expandir el sidebar + breadcrumb.
   - Derecha: selector de idioma (`locale-switcher`), retirado del sidebar.

## Contexto técnico verificado

- Stack: Laravel + Livewire `^4.1` + Flux UI free `livewire/flux ^2.13`.
- Layout activo: `resources/views/layouts/app.blade.php` → `<x-layouts::app.sidebar>` (`resources/views/layouts/app/sidebar.blade.php`). El contenido se renderiza dentro de `<flux:main>`.
- **Persistencia ya incluida en Flux**: el elemento `<ui-sidebar>` guarda el estado en `localStorage["flux-sidebar-collapsed-desktop"]` (JSON) cuando el sidebar tiene `collapsible` (true), y lo lee al iniciar. Con `collapsible="mobile"` (estado actual) NO hay colapso de escritorio. El default de Flux es expandido (`null`), por eso hay que sembrar el valor inicial.
- `<flux:sidebar.toggle>` solo despacha el evento `flux-sidebar-toggle`; funciona desde cualquier parte del DOM (el header móvil actual ya lo usa fuera del sidebar). En desktop alterna el colapso a iconos; en móvil abre/cierra el overlay.
- `flux:breadcrumbs` / `flux:breadcrumbs.item` están disponibles en la edición free. Un item sin `href` se renderiza como texto estático (página actual).
- Páginas existentes: `dashboard.blade.php` (Blade con `<x-layouts::app>`) y settings (componentes Livewire full-page `Profile`, `Security`, `Appearance` con `#[Title]`, sin `render()` explícito; layout por defecto `layouts.app`).
- El header móvil existente (`<flux:header class="lg:hidden">` en `sidebar.blade.php`) ya tiene toggle + locale-switcher: **se mantiene intacto** (decisión del usuario: el nuevo header es solo desktop).
- Ningún test referencia `sidebar` ni `locale-switcher`.

## Cambios

### 1. `resources/views/layouts/app/sidebar.blade.php`

- Cambiar `collapsible="mobile"` por `collapsible` (atributo bare = `true`). Esto habilita el colapso de escritorio a barra de iconos y la persistencia en localStorage; el comportamiento móvil (overlay) se conserva porque `$collapsible === true` también activa `collapsibleOnMobile` en el stub de Flux.
- Eliminar el bloque del selector de idioma del sidebar (líneas 33-35):
  ```blade
  <div class="hidden px-2 pb-2 lg:block">
      <livewire:locale-switcher />
  </div>
  ```
- Mantener `<flux:sidebar.collapse class="lg:hidden" />` (botón cerrar en móvil) y todo el `<flux:header class="lg:hidden">` tal cual.

### 2. `resources/views/partials/head.blade.php`

- Dentro del `<script>` inline existente (mismo patrón que `flux.theme`/`flux.appearance`), sembrar el default **antes** de que Flux arranque (`@fluxScripts` va al final del body):
  ```js
  if (window.localStorage.getItem('flux-sidebar-collapsed-desktop') === null) {
      window.localStorage.setItem('flux-sidebar-collapsed-desktop', 'true');
  }
  ```
  Primera visita = colapsado; cada toggle posterior lo sobrescribe y persiste (Flux hace `JSON.parse` del valor).

### 3. `resources/views/layouts/app.blade.php`

Renderizar el nuevo header dentro de `<flux:main>`, antes del slot, visible solo en desktop:

```blade
<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <div class="max-lg:hidden mb-6 flex items-center gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-700">
            <flux:sidebar.toggle icon="bars-2" inset="left" />

            <flux:breadcrumbs>
                @foreach (($breadcrumbs ?? []) as $item)
                    <flux:breadcrumbs.item :href="$item['href'] ?? null" wire:navigate>
                        {{ $item['label'] }}
                    </flux:breadcrumbs.item>
                @endforeach
            </flux:breadcrumbs>

            <flux:spacer />

            <livewire:locale-switcher />
        </div>

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
```

- El header se muestra siempre en desktop (toggle + idioma son útiles aunque no haya breadcrumbs).
- Convención del prop `breadcrumbs`: array de `['label' => string, 'href' => string|null]`; el último item sin `href` = página actual.

### 4. Pasar breadcrumbs desde cada página

- `resources/views/dashboard.blade.php`:
  ```blade
  <x-layouts::app :title="__('Dashboard')" :breadcrumbs="[['label' => __('Dashboard')]]">
  ```
- Componentes settings (`app/Livewire/Settings/Profile.php`, `Security.php`, `Appearance.php`): agregar `render()` pasando datos al layout (Livewire 4: segundo argumento de `->layout()`):
  ```php
  public function render(): \Illuminate\View\View
  {
      return view('livewire.settings.profile')
          ->layout('layouts.app', [
              'breadcrumbs' => [
                  ['label' => __('Settings'), 'href' => route('profile.edit')],
                  ['label' => __('Profile')],
              ],
          ]);
  }
  ```
  Ajustar vista/etiquetas por componente (`Security` → `__('Security')`, `Appearance` → `__('Appearance')`). No tocar `#[Title]`.

## Decisiones tomadas

- Breadcrumb: **prop por página** (explícito, traducible, escalable). No auto-generar por ruta.
- Header nuevo: **solo desktop** (`max-lg:hidden`). Móvil conserva el `flux:header` existente con su toggle y selector de idioma.

## Riesgos / notas

- Usuarios con una visita previa podrían tener ya la clave en localStorage; el seed solo aplica cuando la clave no existe (correcto: respeta su elección).
- `wire:navigate` no afecta la persistencia (localStorage sobrevive a navegaciones SPA).
- No hay flash de estado: Flux oculta el sidebar con `data-flux-sidebar-cloak` hasta inicializar.
- Cambio puramente de layout/UI: no requiere tests nuevos (ningún test existente toca estas áreas).

## Validación

1. `npm run build` (o verificar con `composer run dev` activo) y recarga completa del navegador.
2. Primera visita a `/dashboard`: sidebar colapsado en iconos; header con toggle, breadcrumb "Dashboard" y selector de idioma a la derecha; el idioma ya no aparece en el sidebar.
3. Expandir con el toggle del header → recargar → sigue expandido. Colapsar → recargar → sigue colapsado.
4. `/settings/profile`, `/settings/security`, `/settings/appearance`: breadcrumb "Settings / <Página>".
5. Móvil (< lg): header móvil existente sigue funcionando (toggle abre overlay, idioma presente); el nuevo header no se muestra.
6. `php artisan test --compact` para confirmar que nada se rompió.
