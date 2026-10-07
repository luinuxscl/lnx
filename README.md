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
| Autorización  | Spatie Laravel Permission (roles `root` y `admin`)     |
| Tests         | Pest                                                  |
| Base de datos  | SQLite por defecto                                     |
| Idiomas       | Español (por defecto) + Inglés                         |

## Nuevo proyecto desde el kit

Para crear un proyecto nuevo basado en este kit (carpeta propia, git limpio, identidad sustituida y app lista):

```bash
./scripts/new-project.sh <nombre> <dominio> [--app-name "Nombre Visible"] \
    [--github-owner OWNER] [--target RUTA] [--skip-setup]
```

Ejemplo:

```bash
./scripts/new-project.sh rukatv-admin admin.rukatv.cl --app-name "Ruka TV Admin"
```

El script copia solo los archivos versionados (`git archive`), inicializa `main`/`develop`, sustituye `APP_NAME`, `APP_URL`, remitente de correo, `composer.json`, README y los enlaces al repositorio, y ejecuta la instalación local. Al terminar, imprime los pasos para crear el repo en GitHub y desplegar en Coolify (skill global `coolify-laravel-deploy`).

El kit es además un **GitHub Template**: también puedes crear el proyecto con `gh repo create <owner>/<nombre> --template luinuxscl/lnx --private`, aunque el script aporta la sustitución de identidad y el setup.

El despliegue de referencia (Coolify + PostgreSQL + `Dockerfile`) usa:

- Imagen `serversideup/php:8.5-fpm-nginx`, puerto `8080`, healthcheck `/up`.
- `AUTORUN_ENABLED=true` con migraciones, seed y `storage:link` al arrancar.
- `trustProxies(at: '*')` en `bootstrap/app.php` para detectar HTTPS tras Traefik.
- El usuario de prueba del `DatabaseSeeder` solo se crea fuera de producción.

## Identidad y valores por defecto

lnx define su identidad por defecto en capas, de modo que una instalación nueva se ve como LNX sin configurar nada:

| Valor | Por defecto | Origen |
|---|---|---|
| Nombre de la app | `LNX` | `APP_NAME` (`config('app.name')`) |
| Lema | `Base de desarrollo Laravel` | `APP_TAGLINE` (`config('app.tagline')`) |
| Texto del footer | `Hecho por Like Innovación` | `APP_FOOTER_TEXT` (`config('app.footer_text')`) |
| Zona horaria | `America/Santiago` | `APP_TIMEZONE` (`config('app.timezone')`) |
| Idioma | Español (`es`); inglés (`en`) disponible | `APP_LOCALE` / `APP_FALLBACK_LOCALE` |
| Logo | `public/branding/lnx-logo.png` | `App\Models\Setting::DEFAULT_LOGO` |
| Favicon / Apple touch icon | `public/favicon.png` / `public/apple-touch-icon.png` | `Setting::DEFAULT_FAVICON` / `Setting::DEFAULT_APPLE_TOUCH_ICON` |

- La fila de `settings` **sobrescribe** estos valores: si el sitio define título, lema, footer, logo o favicon, se usan esos. Con los campos vacíos se aplican los defaults de arriba.
- `App\Models\Setting` expone `title()`, `tagline()`, `footer()`, `logoUrl()`, `logoDarkUrl()`, `faviconUrl()` y `appleTouchIconUrl()` con esos fallbacks. `Setting::logoUrl()` siempre devuelve una URL (el logo del kit si no hay uno subido).
- `new-project.sh` sustituye `APP_NAME` y `APP_URL`; el resto de defaults se pueden cambiar en `.env` o en **Configuración general** (`/admin/settings`).

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
php artisan storage:link
npm run build
php artisan serve
```

## Historial de actividad administrativa

La página **Actividad administrativa** (`/admin/activity`) está disponible exclusivamente para `root`. Muestra actor, fecha, acción y recurso, permite desplegar valores anteriores/nuevos y combina filtros por actor histórico, acción y días desde/hasta. Los filtros se conservan en la URL; la página muestra 10 registros por vez. Las fechas se presentan y los filtros de días se interpretan en la zona horaria del usuario autenticado (`users.timezone`); sin preferencia se usa `config('app.timezone')` (America/Santiago por defecto) y abarcan los días completos.

Se registran las altas por invitación, ediciones, cambios de rol, cambios administrativos de contraseña (solo el hecho), eliminaciones, suspensiones y reactivaciones de usuarios; cambios de configuración e imágenes; y creación, renovación, revocación, aceptación y resultado de envío de invitaciones. Los registros conservan identificadores y copias del nombre y correo del actor y recurso aunque se eliminen. La aceptación se atribuye al destinatario y el alta queda vinculada con su invitación. El historial no contiene contraseñas, hashes de tokens, enlaces de aceptación, secretos 2FA, credenciales de passkeys ni excepciones; para imágenes solo conserva rutas relativas.

Al desplegar, ejecutar `php artisan migrate --force` antes de servir el código nuevo. La migración permite borrar a quien emitió una invitación sin borrar la invitación. El historial comienza con la instalación de esta función: no reconstruye eventos anteriores, no tiene purga automática ni exportación. Quedan fuera el registro público, el perfil personal, accesos, seeders y cambios manuales de consola.

## Centro de notificaciones

La campana de las cabeceras abre `/notifications` para cualquier cuenta autenticada, incluso sin rol. El contador muestra los avisos pendientes y se actualiza al navegar o al marcar avisos como leídos; no hay actualización automática en segundo plano. La bandeja presenta los más recientes primero, diez por página, con fecha en la zona horaria de la cuenta. Abrirla no marca nada como leído. «Marcar todas como leídas» afecta también a las demás páginas.

Aplicar `php artisan migrate --force` para crear la tabla estándar `notifications` antes de servir esta versión. Desde ese momento, un cambio administrativo efectivo de rol, incluida su retirada, genera un aviso dentro de la misma transacción que actualiza el usuario y registra la actividad. El aviso guarda los valores del rol anterior y nuevo, y los traduce cuando se consulta, por lo que sigue siendo legible al cambiar de idioma o al eliminar al administrador. No envía correo ni usa colas.

Cada lectura y escritura de avisos se hace por las relaciones de la cuenta autenticada. Un ID ajeno o inexistente produce 404, también para `root`. Los tipos desconocidos muestran un texto genérico sin interpretar datos, HTML ni enlaces. Para añadir otro tipo interno, crear una clase `Notification` con canal `database`, guardar solo datos estables en `toDatabase()`, emitirla mediante `User::notify()` en la transacción del evento y añadir una rama explícita para traducirla en `App\Livewire\Notifications\Index::message()`. Si el tipo necesita un enlace en el futuro, comprobar la existencia del recurso y la autorización vigente al mostrarlo. No hay borrado, retención automática, filtros, preferencias de canales ni destinos genéricos.

## Suspensión de usuarios

Root y administradores pueden suspender y reactivar usuarios desde **Usuarios**. Ambas acciones solicitan confirmación en un modal. La lista muestra el estado y permite filtrarlo junto con la búsqueda. También pueden gestionar otros administradores. Las cuentas root y la propia cuenta no se pueden suspender.

Suspender conserva contraseña, roles, passkeys, 2FA, avatar y datos relacionados. Bloquea nuevos inicios de sesión y revoca sesiones y cookies de «recordarme» anteriores. Tras reactivar una cuenta, el usuario debe iniciar sesión nuevamente; la reactivación no restaura el acceso previo. La comprobación de versión funciona con los drivers de sesión `array`, `database`, `file` y `cookie`, sin borrar registros de sesiones.

En despliegues, aplicar primero la migración que añade `suspended_at` y `auth_version`. Durante la activación, evitar servir simultáneamente código anterior y nuevo. No revertir los controles mientras existan cuentas suspendidas.

## Sesiones y dispositivos

En **Ajustes → Seguridad** cada usuario ve sus sesiones de navegador activas (navegador, plataforma, IP y última actividad), con la sesión actual marcada como **Este dispositivo**. La acción **Cerrar las demás sesiones de navegador** solicita confirmación de identidad (contraseña o passkey, vía la pantalla de confirmación de Fortify) y cierra el resto de sesiones, manteniendo la actual activa.

El cierre rota el `remember_token` del usuario, por lo que también revoca los inicios de sesión «recordarme» de los otros dispositivos. Es un cierre masivo: no hay revocación individual por dispositivo. Sin confirmación reciente (10 minutos), la acción redirige a la pantalla de confirmación y reabre el diálogo al volver.

La función requiere `SESSION_DRIVER=database`; con otros drivers se muestra un aviso y no se listan sesiones, ya que no es posible consultarlas ni revocarlas.

## Comandos útiles

| Comando                 | Descripción                          |
|-------------------------|--------------------------------------|
| `npm run dev`           | Dev server de Vite (hot reload)      |
| `npm run build`         | Compila assets de producción         |
| `php artisan serve`     | Servidor local (puerto 8000)         |
| `php artisan test`      | Ejecuta la suite de Pest             |
| `vendor/bin/pint`       | Formatea el código PHP               |
| `vendor/bin/phpstan`    | Análisis estático                    |
| `php artisan lnx:reset` | Reinicia el entorno de desarrollo    |

`lnx:reset` es una herramienta de desarrollo (bloqueada en producción): limpia cachés, ejecuta `migrate:fresh`, siembra roles y root, y crea cuentas demo — Samantha (`admin@lnx.dev`, rol `admin`), Leonora (`user@lnx.dev`, sin rol) y 100 usuarios de ejemplo (90 verificados / 10 sin verificar) — todas con contraseña `password`. Opciones: `--faker=0` (sin usuarios de ejemplo), `--no-fresh` (sembrar sobre la base actual sin migrar), `--no-cache`, `--seed=` (datos reproducibles) y `--force` (omite la confirmación). El seeder puede ejecutarse por separado con `php artisan db:seed --class=DemoUserSeeder`.

## Theming

La aplicación soporta selección de tema (color de acento) y modo claro/oscuro/sistema desde **Settings → Appearance**:

- **Colores de acento**: Naranja (por defecto), Teal, Rosa, Violeta, Azul, Esmeralda.
- **Modo**: Light, Dark, System.

Ambas preferencias se guardan en la cuenta y en `localStorage`, de modo que siguen al usuario entre dispositivos. Los visitantes conservan solo el valor local del navegador.

### Cómo funciona

- El gris base de Flux (`zinc`) ya está reasignado a la paleta `neutral` del proyecto en `resources/css/app.css`.
- Cada tema se define como una clase `.theme-{color}` en `resources/css/app.css`, que sobreescribe las variables CSS `--color-accent`, `--color-accent-content` y `--color-accent-foreground` (con variantes `.dark` por tema).
- Al cargar la página, `resources/views/partials/head.blade.php` aplica la clase `theme-*` desde `localStorage['flux.theme']` antes del render para evitar flashes.
- El modo claro/oscuro lo gestiona Flux vía `localStorage['flux.appearance']`.
- Tras cada navegación SPA (`wire:navigate`), el tema y el modo claro/oscuro se re-aplican al escuchar `livewire:navigated` en `resources/views/partials/head.blade.php`. Esto evita que el servidor (que renderiza `class="dark"`) elimine las clases `theme-{color}` cuando sustituye los atributos del elemento `<html>` durante la navegación.

Para añadir un nuevo color de acento: agrega el valor en la constante `App\Support\Appearance::THEMES` y define `.theme-{color}` (+ `.dark.theme-{color}`) en `resources/css/app.css`.

## Preferencias por usuario (idioma, zona horaria y apariencia)

Cada cuenta guarda su idioma (`locale`), zona horaria (`timezone`), tema (`theme`) y modo claro/oscuro/sistema (`color_scheme`) en columnas **nullable** de `users`. Se editan en **Settings → Appearance**, donde el servidor valida cada valor: el idioma debe estar en `config('app.available_locales')`, el tema en `App\Support\Appearance::THEMES`, el modo en `App\Support\Appearance::COLOR_SCHEMES` y la zona debe ser un identificador IANA válido (regla `timezone` de Laravel, opciones desde `DateTimeZone::listIdentifiers()`).

### Cómo se sincroniza

- **Al iniciar sesión y en cada carga autenticada**, `resources/views/partials/head.blade.php` siembra `localStorage['flux.theme']` y `localStorage['flux.appearance']` desde la cuenta antes de que Flux aplique el tema, de modo que las preferencias de la cuenta prevalecen sobre valores antiguos del navegador.
- **Idioma**: `SetLocale` usa primero `users.locale`; el `LocaleSwitcher` y `LocaleController` lo persisten en la cuenta cuando hay sesión iniciada. Los invitados mantienen el idioma de sesión.
- **Modo sistema**: se guarda como `color_scheme = 'system'` y Flux sigue el esquema del dispositivo actual.
- **Zona horaria**: no cambia el almacenamiento (los timestamps siguen en UTC/app tz). Solo afecta la presentación de fechas y los límites de los filtros por día; el helper es `App\Support\Appearance::currentTimezone()`, que cae a `config('app.timezone')` cuando no hay preferencia.
- **Navegación Livewire**: el tema y el idioma se re-aplican en `livewire:navigated` desde el mismo partial.
- Las páginas de ajustes mantienen la autorización existente (`auth` + `verified`); no se introducen permisos nuevos.

Al desplegar, aplicar la migración `add_preferences_to_users_table` con `php artisan migrate --force`.

## Layout de la aplicación

- **Menú agrupado por dominios con prioridad de root**: el sidebar organiza las opciones principales en dominios lógicos usando `<flux:sidebar.group>`. `Dashboard` es siempre el primer ítem. Para `root`, tras `Dashboard` va el grupo **Administración** (`Administration`, `cog-6-tooth`) con sus herramientas (Soporte, Usuarios, Actividad, Ajustes, Checklist e IA) y después el grupo **Herramientas de IA** (`AI Tools`) con Chat, RAG y Document AI; se omiten los ítems sin destino `Repository` y `Documentation`. Para `admin` y usuarios normales el orden es `Dashboard`, **Herramientas de IA** y, tras el espaciador, la sección **Administración** (`Users` y `Documentation`), visible solo para `admin`.
- **Sidebar colapsable** (desktop): el `<flux:sidebar collapsible>` se colapsa a barra de iconos. El estado se persiste en `localStorage['flux-sidebar-collapsed-desktop']` (gestionado por Flux). `resources/views/partials/head.blade.php` siembra el valor `true` en la primera visita, por lo que arranca **colapsado por defecto**; cada toggle posterior lo sobrescribe. En móvil conserva el overlay. Al colapsar en escritorio, los grupos desplegables (`<flux:sidebar.group>`) se convierten automáticamente en un flyout dropdown hover.
- **Botón de colapso en el sidebar**: `<flux:sidebar.collapse>` en el header del sidebar (patrón oficial de Flux). Al colapsar, el logo se oculta al hacer hover y es sustituido por el botón de expansión; por eso el botón debe estar presente en desktop (no usar `lg:hidden`).
- **Marca del sidebar**: junto al logo, `x-app-logo` muestra el título en negrita y, debajo en texto más pequeño, `tagline()` (configurado o el default `APP_TAGLINE`). Si no hay lema, solo se muestra el título; si falta el título configurado, se usa `APP_NAME`. Los textos largos se truncan para dejar disponible el botón de colapso.
- **Header de contenido** (solo desktop, `max-lg:hidden`): se renderiza dentro de `<flux:main>` en `layouts/app.blade.php` con el breadcrumb (`flux:breadcrumbs`) a la izquierda y, a la derecha, el botón `Messages` (enlace a `/messages`), la campana de notificaciones, el botón de modo claro/oscuro (`$flux.dark`) y el selector de idioma. En móvil, el botón `Messages` aparece junto a la campana en el header móvil del sidebar. El colapso del sidebar se controla solo desde su propio botón en el header del sidebar.
- **Breadcrumbs por página**: cada página pasa un arreglo `['label' => string, 'href' => string|null]`; el último item sin `href` es la página actual. En Blade: `<x-layouts::app :breadcrumbs="[...]">`; en componentes Livewire full-page, `render()` devuelve `view(...)->layout('layouts.app', ['breadcrumbs' => [...]])`.
- **Alto del contenido**: `<flux:main>` es una columna flex (`flex flex-col`) para que el header de contenido y los banners convivan con la página. Una página que debe ocupar el alto restante usa `flex-1` en su raíz, **nunca `h-full`**: `h-full` reclama el 100% del alto de `main` y, al sumarse el header/banner, desborda la fila del grid; ese desborde impide que la fila crezca y rompe el sidebar sticky (el sidebar deja de cubrir todo el alto y aparece un hueco al hacer scroll).

## Avatares de usuario

Cada usuario puede subir, reemplazar o quitar su avatar en **Ajustes → Perfil**. Se aceptan JPEG, PNG y WebP de hasta 2 MB. La imagen subida tiene prioridad; si no existe o no se puede cargar, se intenta obtener el Gravatar del correo del usuario. Si Gravatar tampoco ofrece una imagen, se muestran las iniciales actuales.

Los avatares se guardan en el disco `public` (`storage/app/public/avatars`). La instalación y el despliegue deben mantener el enlace creado por `php artisan storage:link`. Al reemplazar o quitar un avatar, o al eliminar la cuenta, se borra el archivo anterior.

## Localización (i18n)

La aplicación es bilingüe: **español (por defecto)** e **inglés**. Los visitantes guardan el idioma en la sesión; los usuarios autenticados lo guardan además en su cuenta. Se aplica en cada request mediante `App\Http\Middleware\SetLocale`, que da prioridad al idioma de la cuenta sobre la sesión.

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

Toda vista nueva o modificada debe crearse **siempre en ambos idiomas**: envuelve los textos en `__()` y añade la clave con su traducción en `lang/es.json` y `lang/en.json`. Los títulos de página pasan por `__($title)` en `resources/views/partials/head.blade.php`, por lo que `#[Title('Profile settings')]` también se traduce. Se preservan los anglicismos y acrónimos estándar de la industria tecnológica (`Dashboard`, `Passkeys`, `Chatbot`, `RAG`, `2FA`, `MCP`, `API`, `Tokens`) sin traducirlos literalmente en la interfaz en español.


## Formato numérico estándar

La aplicación estandariza el formateo de números en toda la interfaz utilizando **punto (`.`) para separadores de miles** y **coma (`,`) para separadores decimales** (ejemplo: `1.234.567,89`).

- **Configuración global**: `AppServiceProvider` establece `Illuminate\Support\Number::useLocale('es')` por defecto para que las utilidades nativas de Laravel (`Number::format()`, `Number::currency()`, `Number::fileSize()`, etc.) apliquen esta convención automáticamente.
- **Helper de formato**: La clase `App\Support\NumberFormat` proporciona métodos estáticos convenientes:
  - `NumberFormat::format($value, ?int $precision = null)` — formatea enteros o decimales (ej. `1.234,56`).
  - `NumberFormat::currency($value, string $currency = 'USD', int $precision = 4)` — formatea montos monetarios (ej. `1.234,5678 USD`).
  - `NumberFormat::fileSize($bytes, int $precision = 1)` — formatea tamaños de archivos (ej. `1,5 MB` o `1.024,0 KB`).
- **Regla en vistas**: Al presentar contadores, métricas, tablas, uso de IA o montos numéricos en Blade y Livewire, utiliza `Number::format()` o `App\Support\NumberFormat` para mantener la consistencia en todos los idiomas.

## Autorización (roles)

La app usa **spatie/laravel-permission** y trabaja **por roles, no por permisos granulares**. Roles por defecto (`App\Enums\Role`):

- **`root`** — superusuario. Se configura con `Gate::before` en `AppServiceProvider`: omite todas las comprobaciones de gates y policies.
- **`admin`** — rol administrativo, sin bypass global.

### Detalles

- El trait `HasRoles` está aplicado en `App\Models\User` (`$user->assignRole(Role::Root->value)`, `$user->hasRole(...)`).
- Los alias de middleware `role`, `permission` y `role_or_permission` están registrados en `bootstrap/app.php` (p. ej. `->middleware('role:admin')`).
- `RolePermissionSeeder` crea los roles de forma idempotente y, si `ROOT_USER_EMAIL` y `ROOT_USER_PASSWORD` están definidos, siembra el usuario root inicial. Se ejecuta desde `DatabaseSeeder`.
- Variables de entorno del usuario root (ver `config/authorization.php`): `ROOT_USER_NAME`, `ROOT_USER_EMAIL`, `ROOT_USER_PASSWORD`.
- El registro público (Fortify) **no** asigna ningún rol por defecto.

### Credenciales root de desarrollo y alerta de seguridad

Para desarrollo, `.env.example` trae un root listo para usar (**Luis Sepulveda / luinuxscl@gmail.com / password**) para que `php artisan migrate --seed` deje la app operativa al instante. Esas credenciales **nunca** deben llegar a producción, y por eso existen tres capas de defensa:

1. **Bloqueo del seeder en producción**: si el entorno es `production` y `ROOT_USER_PASSWORD` sigue siendo el password de desarrollo (`App\Support\DevRootCredentials::PASSWORD`), `RolePermissionSeeder` lanza una excepción y no crea el usuario.
2. **Log crítico**: con esas credenciales activas en producción, los comandos de consola registran un `Log::critical`; al seedear en local se emite además un warning en consola y en el log.
3. **Banner en la UI** (todos los entornos): mientras un usuario `root` conserve el password de desarrollo, `resources/views/components/dev-credentials-warning.blade.php` muestra un aviso con enlace directo a cambiar la contraseña.

Para producción basta con definir un `ROOT_USER_PASSWORD` fuerte (o vaciar `ROOT_USER_EMAIL`) antes de seedear.

### Gestión de usuarios e invitaciones

La gestión de usuarios en `/admin/users` está disponible para `root` y `admin`. La ruta de creación envía una invitación por correo: el administrador indica correo, rol (`admin` o ninguno) e idioma (español o inglés). No define una contraseña ni crea la cuenta. La pantalla **Invitaciones** permite buscar, consultar estados, reenviar y revocar; el reenvío invalida el enlace anterior y conserva el idioma elegido.

El enlace vence a los siete días y solo se puede aceptar una vez. El destinatario define nombre y contraseña; el correo queda verificado y se inicia su sesión. Un enlace vencido, revocado o utilizado no crea cuentas, y una cuenta ya existente nunca se sobrescribe. No es posible invitar a un nuevo `root`. Quien ya tenga sesión debe cerrarla antes de aceptar.

El correo se envía durante la solicitud para mostrar un fallo inmediatamente. La invitación queda guardada como **Falló el envío** y puede reenviarse desde la lista. Un éxito significa que el transporte aceptó el envío; la entrega final depende del proveedor. Configura `MAIL_MAILER` y las credenciales del proveedor antes de invitar. Los intentos se limitan a uno por minuto y cinco por hora por destinatario, y veinte por hora por administrador; los fallos también cuentan.

Las cuentas `root` no se pueden editar ni eliminar desde la gestión de usuarios. Nadie puede eliminar su propia cuenta. El ítem «Usuarios» vive en el grupo **Administración** del sidebar (ver `.ai/rules/views.md`).

### Modalidad de registro

En **Configuración general**, root puede elegir **Registro abierto** o **Solo invitaciones**. El valor inicial es registro abierto, también en instalaciones existentes que todavía no tienen una fila de configuración. En «Solo invitaciones» se ocultan los enlaces de registro público y tanto GET como POST de `/register` responden 403; los enlaces de invitación válidos siguen funcionando. El cambio se aplica al guardar, sin reconstruir la caché de rutas.

## Configuración general (General Settings)

Vista de configuración global del sitio en `/admin/settings` (`admin.settings.edit`), accesible **solo para `root`** mediante el middleware `role:root` (`routes/settings.php`). El ítem "Settings" vive en el grupo **Administración** del sidebar y solo lo ve root.

- `App\Livewire\Settings\General` + `App\Policies\SettingPolicy` (view/update solo root) gobiernan la vista.
- La configuración se guarda en una fila única de la tabla `settings` (`App\Models\Setting`). `Setting::current()` la cachea bajo la clave `settings` y `Setting::forgetCache()` la invalida al guardar.

### Campos configurables

| Sección | Campo | Efecto |
|---|---|---|
| Identidad | Title | `config('app.name')`, `<title>`, marca del sidebar. Sin valor cae a `APP_NAME` (`LNX`). |
| Identidad | Tagline | Lema del sitio bajo el título del sidebar (y fallback de la meta descripción). Vacío usa `APP_TAGLINE`. |
| Identidad | Meta description | `<meta name="description">` (fallback: Tagline). |
| Identidad | Administration email | Destinatario del correo de prueba del checklist y validación de identidad; no altera el remitente de correo. |
| Personalización | Logo / Dark mode logo | Logo del encabezado, sidebar y pantallas de autenticación; el logo personalizado se muestra sobre fondo transparente. Sin archivo se usa el logo del kit. |
| Personalización | Favicon / Apple touch icon | Iconos del `<head>`. Sin archivo se usan `favicon.png` y `apple-touch-icon.png` del kit. |
| Avanzado | Footer text | Texto mostrado en el footer del contenido de las páginas autenticadas. Vacío usa `APP_FOOTER_TEXT`. |
| Avanzado | Analytics ID | ID de GA4 inyectado en el `<head>` (solo en producción). |
| Avanzado | Maintenance mode + message | Bloquea la app con una página 503; root y las rutas de auth siguen accesibles. |

### Cómo se aplica

- El footer del contenido muestra el `footer_text` (por defecto `Hecho por Like Innovación`) y la marca Like Innovación. Solo `admin` y `root` ven la versión de LNX, definida con `APP_VERSION` (valor predeterminado `0.1.0`).
- `App\Http\Middleware\ApplySiteSettings` (grupo `web`) vuelca los settings en `config()` y comparte `$site` con todas las vistas; `resources/views/partials/head.blade.php`, `resources/views/components/app-logo.blade.php` y el sidebar lo consumen.
- El remitente de los correos (`config('mail.from.address')`) proviene de `MAIL_FROM_ADDRESS` y debe pertenecer a un dominio verificado en el proveedor (por ejemplo, Resend). El email de administración es solo un destinatario y no lo sobrescribe.
- `App\Http\Middleware\CheckMaintenanceMode` (grupo `web`) devuelve la vista `errors/maintenance.blade.php` (503) cuando el modo mantenimiento está activo. Los usuarios root y las rutas de autenticación quedan exentos para poder desactivarlo.
- Los archivos de branding se suben al disco `public` (`storage/app/public/branding`); por eso `php artisan storage:link` es obligatorio en instalación y despliegue.

## Panel por rol

`/dashboard` (`dashboard`) mantiene los middleware `auth` y `verified`. La bienvenida muestra el nombre escapado. El panel es un componente Livewire de clase que comprueba los roles y las policies en cada renderizado; sus datos se pasan solo a la vista, sin conservarlos en propiedades públicas.

**root**: el dashboard es un panel de acceso a herramientas mediante tarjetas-enlace (`x-tool-card`). La sección principal **Administración** reúne las herramientas de root —Soporte, Usuarios, Actividad, Configuración, Checklist e IA— y la sección secundaria **Herramientas de la app** enlaza Chat, Messages, RAG y Document AI. Cada tarjeta navega a la página de detalle del área; el dashboard no muestra cifras ni resúmenes (queda abierto a añadir métricas o widgets de root más adelante). El detalle de cada área, incluido el consumo de IA, vive en su propia pantalla.

**admin**: ve usuarios totales, activos (`suspended_at` nulo) y suspendidos (`suspended_at` presente), junto con los accesos de cuenta (perfil y seguridad). Los conteos incluyen **todas las cuentas**, también las de root y admin. Puede abrir la lista completa, los filtros `status=active` y `status=suspended`, invitaciones y la creación de una invitación. Las invitaciones pendientes son las no aceptadas ni revocadas cuyo vencimiento es futuro, aunque haya fallado el envío; si no hay ninguna, el panel lo indica expresamente.

El usuario normal ve solo sus accesos de cuenta. El dashboard ya no ejecuta diagnósticos ni la sonda de almacenamiento; el checklist y sus comprobaciones viven en `/admin/setup` (`App\Actions\DiagnoseSetup`).

## Checklist de puesta en marcha

Vista en `/admin/setup` (`admin.setup.index`), accesible **solo para `root`** mediante el middleware `role:root` (`routes/web.php`). `App\Livewire\Setup\Index` muestra cinco pasos para verificar que la instalación quedó utilizable:

1. **Identidad del sitio**: verificado cuando hay `site_title` y un `admin_email` con formato válido.
2. **Correo de prueba**: botón que envía una notificación on-demand a `admin_email` (o al correo del root si no hay ninguno) y persiste el resultado en la fila `settings`. El remitente es `MAIL_FROM_ADDRESS`, que debe pertenecer a un dominio verificado en el proveedor; no es el email de administración. Limitado a cinco intentos por hora y por usuario; los fallos cuentan. Un éxito solo significa que el transporte aceptó el mensaje, nunca que llegó al buzón. Un fallo de transporte se registra con `report()` y jamás expone el mensaje de la excepción (puede filtrar credenciales).
3. **Almacenamiento**: diagnóstico en vivo del enlace `public/storage` y de una escritura/lectura/borrado de prueba en el disco `public`; el archivo temporal se elimina siempre. Se puede re-ejecutar con «Re-check storage».
4. **Modalidad de registro**: verificado cuando ya existe una fila persistida en `settings`, es decir, root guardó configuración al menos una vez.
5. **IA**: pendiente mientras la integración está desactivada (una instalación nueva no tiene credenciales); verificado cuando está activada, hay clave y el modelo configurado figura en la lista permitida; con error cuando está activada pero mal configurada. Enlaza a `/admin/ai`.

El estado de cada paso se recalcula al cargar la página desde el estado real del sistema; solo el resultado del correo de prueba se persiste (`test_email_sent_at`, `test_email_sent_to`, `test_email_failed_at` en `settings`). Si `admin_email` cambia desde el último envío exitoso, el paso vuelve a pendiente. En despliegue, la migración de esas columnas se aplica con `php artisan migrate --force`.

## Servidor MCP (lectura)

lnx expone un servidor [Laravel MCP](https://laravel.com/docs/mcp) de **solo lectura** para que agentes externos consulten la aplicación con operaciones estables y autorizadas. No traslada herramientas de consola, base de datos ni acceso a archivos: no expone ejecución de PHP/SQL/shell ni lectura genérica de ficheros. El piloto cubre cuatro capacidades y no permite escrituras.

### Habilitación y transporte

- El servidor viene **desactivado**: `MCP_SERVER_ENABLED=false`. Mientras esté apagado, la ruta responde `404` y ninguna credencial sirve.
- Al activarlo se sirve un transporte **web** en `POST /{MCP_SERVER_PATH}` (por defecto `/mcp`). Las rutas MCP no usan el grupo `web`: no hay sesión ni CSRF, solo el token.
- Variables (ver `config/mcp_server.php`): `MCP_SERVER_ENABLED`, `MCP_SERVER_PATH`, `MCP_SERVER_PER_PAGE`, `MCP_SERVER_MAX_PER_PAGE`, `MCP_SERVER_RATE_LIMIT`, `MCP_SERVER_AUDIT_RETENTION_DAYS`, `MCP_SERVER_TOKEN_EXPIRES_DAYS`.
- Se registra además `routes/ai.php`, cargado automáticamente por el paquete `laravel/mcp`.

### Autenticación y credenciales

Cada conexión se autentica con un **token Bearer propio, revocable y ligado a un usuario**:

- Se crean y revocan en **Ajustes → Seguridad → MCP access tokens**. El texto plano se muestra una sola vez; en la base solo se guarda `sha256`.
- Ausencia de token, token inválido, revocado o vencido → `401`. Cuenta suspendida → `403`. El servidor comprueba el estado en **cada** invocación, no solo al conectar.
- La expiración es opcional (`MCP_SERVER_TOKEN_EXPIRES_DAYS`); sin valor, el token vive hasta revocarse.
- Límite de frecuencia por identidad (`MCP_SERVER_RATE_LIMIT` por minuto) → `429` con `Retry-After`.
- No se admite el transporte `stdio` local: no ofrece identidad por conexión ni revocación.

### Capacidades y autorización

Las reglas vigentes (policies) se reutilizan en servidor en el descubrimiento y en cada consulta, así que una herramienta no autorizada ni siquiera aparece:

| Herramienta | Rol | Contenido |
| --- | --- | --- |
| `list-capabilities` | cualquiera autenticado | Capacidades disponibles para el actor. |
| `get-public-configuration` | `root` | Lista blanca explícita de configuración no sensible (identidad, idioma, zona horaria, registro, mantenimiento). Nunca secretos ni valores de entorno. |
| `search-users` | `root`/`admin` | Búsqueda de usuarios por nombre/correo y estado, con paginación (sin contraseñas, 2FA ni tokens). |
| `get-administrative-activity` | `root` | Historial administrativo con filtros y paginación. |

### Auditoría y retención

Cada invocación registra en `mcp_tool_invocations` actor (con copia de nombre/correo), herramienta, fecha, resultado (`success`/`denied`/`invalid`/`unauthenticated`) y un `correlation_id` (también devuelto en la cabecera `X-Correlation-Id`). Se guarda solo contexto estructural no sensible (paginación, estado, fechas); **nunca** el texto libre de búsqueda ni el contenido de la respuesta. La retención se controla con `MCP_SERVER_AUDIT_RETENTION_DAYS` y la purga `php artisan mcp:prune-invocations`, programada a diario.

### Añadir una herramienta

1. Crea la clase en `app/Mcp/Tools` extendiendo `App\Mcp\Tools\ReadOnlyTool` (ya aplica `#[IsReadOnly]`).
2. Define `authorize(User $user)` reutilizando una policy (`$user->can('viewAny', Modelo::class)`), `perform(Request $request)` con `Response::structured(...)`, y `shouldRegister(Request $request)` con la misma comprobación para el descubrimiento.
3. Añade `schema()`/`outputSchema()`, `#[Name('...')]` y `#[Description('...')]`, y valida argumentos con `$request->validate(...)`.
4. Regístrala en `App\Mcp\Servers\LnxServer::$tools` y descríbela en `App\Mcp\Capabilities` si debe aparecer en el descubrimiento.
5. Reutiliza acciones/policies existentes para no duplicar ni debilitar validaciones, y añade tests de acceso permitido/denegado, contratos y auditoría.

Las descripciones y mensajes de las herramientas van en inglés (consumidos por el agente); la UI y los mensajes de error de la aplicación siguen siendo bilingües.

### Desarrollo y producción

- Mantén el servidor **apagado por defecto** y actívalo solo donde deba operar; en producción exige HTTPS para que el token no viaje en claro.
- Usa credenciales distintas por entorno y revócalas al rotar. No reutilices el token de producción en desarrollo.
- Prueba localmente con `php artisan mcp:inspector mcp` (usa un token real) o con el cliente MCP de tu agente apuntando a `POST` con `Authorization: Bearer <token>`; guarda la evidencia del caso real en el PR.

## Integración de IA (Laravel AI SDK + OpenRouter)

lnx integra el [Laravel AI SDK](https://laravel.com/framework/docs/13.x/ai-sdk) con **OpenRouter** como proveedor principal y una API key compartida del despliegue. No se usan claves directas de OpenAI, Anthropic o Google ni claves por usuario. La capa de aplicación vive en `app/Ai/` y `config/lnx_ai.php`.

### Habilitación y configuración

- La integración viene **desactivada**: `AI_ENABLED=false`. Mientras esté apagada ninguna función llama al proveedor y no se requiere credencial.
- Variables (ver `config/lnx_ai.php`): `AI_ENABLED`, `AI_PROVIDER` (por defecto `openrouter`), `AI_MODEL`, `AI_TIMEOUT`, `AI_MAX_OUTPUT_TOKENS` y el bloque `AI_CONNECTION_TEST_*`.
- La credencial se lee de `OPENROUTER_API_KEY` (config del SDK, `config/ai.php`). Nunca se muestra ni se envía al navegador; la UI solo indica si está configurada.
- `AI_MODEL` debe estar además en la allowlist: `allowed_models` de `config/lnx_ai.php` o `AI_ALLOWED_MODELS` (JSON). Es una lista explícita de modelos con sus **capacidades** (`tools`, `structured`, `streaming`, `context`). No se fija ningún modelo comercial en el código: el despliegue declara los que usa.
- No hay failover automático: `App\Ai\AiGateway` pasa siempre proveedor y modelo explícitos, así que el SDK nunca cambia de proveedor o modelo por su cuenta.

### Activación en una instalación nueva

1. `composer install` y arranca la app como siempre; todo funciona sin credenciales de IA.
2. Declara el modelo en `config/lnx_ai.php` (`allowed_models`) o, sin tocar código, con `AI_ALLOWED_MODELS` (JSON). Ejemplo:

   ```php
   'allowed_models' => [
       'vendor/model-id' => ['tools' => false, 'structured' => false, 'streaming' => true, 'context' => 128000],
   ],
   ```

   ```dotenv
   AI_ALLOWED_MODELS={"vendor/model-id":{"tools":false,"structured":false,"streaming":true,"context":128000}}
   ```

3. En `.env`: `AI_ENABLED=true`, `AI_MODEL=vendor/model-id` y `OPENROUTER_API_KEY=...`.
4. Entra como `root` en **IA** (`/admin/ai`), revisa el estado y pulsa **Probar conexión**.

### Estado y prueba de conexión

`/admin/ai` (`admin.ai.index`) es exclusivo de `root`. Muestra proveedor, modelo, si la clave está configurada, límites y la lista permitida con sus capacidades, y permite lanzar **manualmente** una prueba de conexión limitada (timeout y salida acotados, limitada por frecuencia y por usuario). La prueba **nunca** se dispara al abrir la página y cada ejecución puede ser facturable. Los fallos se reducen a una categoría (autenticación, saldo/cuota, modelo incompatible, tiempo de espera, proveedor no disponible, límite de frecuencia) y se registran sin el mensaje de la excepción, para no filtrar secretos ni el cuerpo de la respuesta.

### Validación y pruebas

`App\Ai\AiConfig` valida en servidor que la IA esté activada, la credencial exista, el modelo esté permitido y declare las capacidades requeridas; `App\Ai\AiGateway` rechaza cualquier petición que no cumpla antes de llamar al proveedor. Las pruebas usan `ConnectionTestAgent::fake(...)` (respuestas simuladas) y no realizan llamadas externas.

### Extender en un proyecto derivado

Reutiliza `App\Ai\LnxAgent` como base de tus agentes (hereda proveedor, modelo, límite de salida y timeout de la configuración) y pasa siempre por `App\Ai\AiGateway`: aplica la validación y, desde #13, el **control de cuota y el registro de consumo**. Declara tu función como una `application` (por ejemplo `text_assistant`) para que tenga sus propios límites y aparezca en el panel. Añade tu modelo a `allowed_models` y cubre la acción nueva con pruebas de respuestas simuladas.

## Cuotas y consumo de IA

`App\Ai\AiGateway` es el único punto por el que pasan las funciones de IA, y desde ahí se aplican los **límites locales** y se registra el **consumo**. Nada se envía al proveedor sin superar antes la reserva de cuota.

### Límites

- Cada petición debe cumplir **dos dimensiones**: el límite **por usuario** (`limits.user`) y el límite de la **aplicación/función** (`limits.applications`, con `limits.default` como respaldo).
- Cada límite declara `window` (`minute`, `hour` o `day`), `requests` y `tokens`. Un valor `0` desactiva esa métrica. Las ventanas son fijas (inicio de minuto/hora/día), no deslizantes.
- Variables (ver `config/lnx_ai.php` y `.env.example`): `AI_USER_LIMIT_*`, `AI_DEFAULT_LIMIT_*`, `AI_CONNECTION_TEST_LIMIT_*` y `AI_TEXT_ASSISTANT_LIMIT_*`. No hay límites fijos en el código.
- La prueba de conexión (#12) es la aplicación `connection_test` y tiene sus propios límites; además conserva su límite de frecuencia por minuto.
- El asistente de texto (#14) es la aplicación `text_assistant` y tiene sus propios límites.

### Reserva atómica y reconciliación

Antes de llamar al proveedor, `App\Ai\AiQuota` reserva **una solicitud y el máximo de tokens de salida** en la tabla `ai_quota_counters` dentro de una transacción con la fila bloqueada, de modo que peticiones concurrentes no pueden superar un límite en el borde de la ventana. Al responder el proveedor:

- **Éxito**: se libera la reserva y se suman los tokens reales (`input + output`) al contador de consumo.
- **Fallo o consumo desconocido**: se libera la reserva, pero el intento queda registrado y cuenta como solicitud, porque pudo generar consumo. Así un fallo no bloquea peticiones posteriores y los reintentos no multiplican el límite.

La reserva usa el máximo de salida porque el consumo real se conoce solo después de la llamada; el contador de tokens combina consumo real más reservas en vuelo. Los límites locales **no** garantizan un tope exacto en la factura de OpenRouter.

### Registro de consumo

Cada intento se guarda en `ai_usage_events` con actor (id y copia de nombre/correo), aplicación/función, proveedor, modelo solicitado y efectivo, tokens, duración, estado, categoría de error y `correlation_id`. Los intentos que comparten `correlation_id` forman una **única petición lógica**: los reintentos se numeran por `attempt` y no duplican el registro lógico. Nunca se guardan credenciales ni el contenido de los prompts.

### Coste

- **Reportado**: OpenRouter informa `usage.cost` en la respuesta; se guarda tal cual (USD).
- **Estimado**: si no hay coste reportado pero el despliegue declara tarifas en `pricing.models` (con `pricing.version`/`pricing.updated_at` y `pricing.currency`), se estima a partir de los tokens y se registra la moneda y la versión de tarifas aplicada.
- **Desconocido**: sin coste reportado ni tarifas, el coste queda **vacío**, nunca cero. Una estimación nunca se presenta como factura.

### Panel y dashboard

- **Consumo de IA** (`/admin/ai/usage`, `admin.ai.usage`) es exclusivo de `root` y está separado del historial administrativo. Permite filtrar por período, usuario, función, modelo y estado, y muestra solicitudes, tokens, coste reportado, estimado y desconocido. Se enlaza desde `/admin/ai`.
- El dashboard de `root` muestra un resumen del **mes en curso** con enlace al panel, sin duplicar sus datos.

### Retención

`AI_USAGE_RETENTION_DAYS` (por defecto 90) controla la retención. El comando `ai:prune-usage`, programado a diario, elimina los registros más antiguos; `0` desactiva la purga.

### Verificación

Las pruebas usan `ConnectionTestAgent::fake(...)` y respuestas simuladas, sin llamadas externas, y cubren bloqueo por límite, concurrencia de la reserva, reconciliación en éxito y fallo, coste reportado/estimado/desconocido, autorización del panel y purga.

## Feedback y soporte

Todas las vistas autenticadas incluyen «Feedback y soporte» en la barra superior: botón con texto en escritorio e icono accesible en móvil. Su menú ofrece crear una solicitud y ver las solicitudes propias. La primera opción abre un panel sobre la pantalla actual para enviar un problema, idea de mejora o consulta, con título, descripción y una indicación opcional de bloqueo. Al abrirlo se registran la ruta con nombre, fecha, usuario, versión de la aplicación si existe, dimensiones de ventana, idioma y tema. No se leen parámetros de URL ni valores de formularios. La captura de pantalla requiere la acción y permiso del usuario, ofrece vista previa y puede descartarse; pegar o cargar una imagen es una alternativa. Se admiten PNG, JPEG y WebP hasta 5 MB. Los archivos se guardan en el disco privado `local` y se entregan únicamente mediante una ruta que comprueba el ticket y la visibilidad de la nota.

«Ver mis solicitudes» muestra el historial propio, incluido el de root. Root dispone además de la bandeja de soporte en el menú Administración, con filtros por estado, tipo, prioridad, autor y pantalla; puede responder, escribir notas internas, asignar prioridad, resolver o cerrar con explicación. Admin solo tiene acceso a sus propias solicitudes. El solicitante puede responder a un ticket no cerrado y reabrir uno resuelto. Las transiciones y la autorización viven en `App\Support\TicketService`, compartido por los componentes Livewire y disponible para una futura integración de mensajería. Los eventos `SupportTicketCreated`, `SupportPublicReplyAdded` y `SupportTicketStatusChanged` señalan cambios explícitos; el ticket sigue siendo la fuente oficial del historial. Las notas internas nunca aparecen en la vista del solicitante ni generan avisos para él.

El asistente de texto ofrece una propuesta de título y redacción para la descripción, que el usuario acepta o descarta. El panel permanece abierto después de solicitar la propuesta; crear el ticket requiere pulsar «Enviar solicitud». Solo la descripción se envía al proveedor mediante la cuota y registro de uso existentes; una captura adjunta nunca se envía a IA. Si falla, el envío manual sigue disponible. Los avisos se guardan en el centro de notificaciones de la aplicación; no se envía correo. La mensajería interna está integrada en `/messages` ([#34](https://github.com/luinuxscl/lnx/issues/34)).

## Mensajería interna (usuarios, sistema e IA)

La aplicación incorpora un sistema de mensajería interna accesible en `/messages` desde el botón `Messages` del header (junto a la campana de notificaciones):

- **Conversaciones directas**: Cualquier usuario autenticado y activo puede iniciar un chat directo 1 a 1 con otro usuario activo. Si ya existe una conversación entre ambos, se reutiliza automáticamente sin duplicar canales.
- **Grupos y comunicados**: Los roles `root` y `admin` pueden crear conversaciones grupales con múltiples participantes o enviar comunicados generales (`announcements`). En los comunicados, los destinatarios tienen rol de receptor, sus identidades no se exponen al resto y las respuestas están bloqueadas para evitar hilos no deseados.
- **Privacidad y autorización estricta**: Ni `root` ni `admin` pueden acceder a conversaciones privadas ajenas a menos que sean participantes legítimos en ellas (el bypass general de superusuario en `Gate::before` excluye expresamente las conversaciones privadas no vinculadas a soporte). Los usuarios suspendidos no pueden iniciar conversaciones ni enviar mensajes.
- **Remitentes de sistema y agentes de IA**: El servicio de mensajería admite mensajes de sistema e IA emitidos por operaciones controladas del servidor (`sender_type` = `system` o `ai`), mostrando la identidad visible del agente y el usuario responsable cuando actuó por delegación. Las peticiones del cliente no pueden suplantar estos remitentes.
- **Integración con soporte**: Las conversaciones vinculadas a tickets de soporte sincronizan automáticamente la descripción del ticket, las réplicas públicas y los cambios de estado como mensajes informativos del sistema. Las respuestas enviadas desde la interfaz de mensajería se delegan a `TicketService::reply()`, manteniendo el ticket de soporte como única fuente oficial del historial. Las notas internas de soporte **nunca** se exponen en la mensajería ni se hacen accesibles al solicitante.
- **Notificaciones e idempotencia**: Los mensajes no leídos generan avisos persistentes en el centro de notificaciones (`/notifications`) evitando duplicidades con soporte. Todas las operaciones de envío soportan `request_key` para garantizar idempotencia ante reintentos.
- **Búsqueda en la conversación**: La cabecera del chat permite filtrar los mensajes de la conversación activa por texto, resaltando las coincidencias dentro de las burbujas y mostrando el número de resultados.
- **Lectura fluida**: El hilo se posiciona automáticamente en el último mensaje, ofrece un botón flotante para volver al final cuando se está leyendo historial y se actualiza en vivo cada 5 segundos mientras la conversación está abierta.
- **Diseño tipo messenger/WhatsApp**: Burbujas ceñidas al contenido con padding compacto, texto a la izquierda, remitente a la izquierda y usuario autenticado a la derecha, hora compacta (`H:i`) para mensajes de hoy y fecha + hora (`d/m H:i`) para anteriores, y separadores de fecha agrupando por día.
- **Agrupación de mensajes**: Los mensajes consecutivos del mismo remitente dentro de una misma ventana de 5 minutos se agrupan visualmente (espaciado reducido, esquina superior unificada y marca de hora solo en el último del bloque). En chats de grupo, el nombre del remitente se muestra sobre el primer mensaje de cada bloque.
- **Compositor multilínea**: El área de escritura es un `textarea` que crece automáticamente hasta 160px y admite saltos de línea. `Enter` envía el mensaje y `Shift+Enter` inserta una nueva línea; el envío respeta el límite de 5000 caracteres.

## Asistente de texto (mejorar, resumir, traducir)

El kit incluye un asistente de texto reutilizable construido sobre la capa de IA. El usuario autorizado elige **mejorar**, **resumir** o **traducir** (con idioma de destino), genera una propuesta y decide aceptarla o descartarla. La generación nunca guarda: el cambio llega al formulario solo al aceptar y descartar conserva el texto original.

### Uso en un formulario

El componente es un Livewire anidado que se enlaza con `wire:model` a una propiedad del componente contenedor. Solo envía el texto enlazado, la acción y el idioma: **nunca lee otros campos del formulario**.

```blade
<livewire:ai.text-assistant wire:model="description" ability="use-text-assistant" />
```

- **Autorización**: `ability` es una habilidad de `Gate` que define el proyecto. El kit declara `use-text-assistant` como solo `root`; los proyectos derivados pueden pasar su propia habilidad (Gate o policy) para abrirlo a otros roles o usuarios. `root` la omite vía `Gate::before`. La comprobación se repite en servidor al generar, aceptar y renderizar.
- **Aceptar y descartar**: aceptar escribe la propuesta en la propiedad enlazada (el formulario la recibe); descartar conserva el texto. La generación no persiste nada por sí sola.
- **Límites y seguridad**: el servidor valida tamaño de entrada, acción e idioma, y limita la salida con `max_output_tokens`. La salida se renderiza como texto escapado, sin ejecutar HTML. Editar el texto o cambiar de acción/idioma invalida una propuesta pendiente, y las respuestas tardías o repetidas no sobrescriben una edición posterior.
- **Errores**: si falta configuración, se alcanza un límite local o el proveedor falla, se muestra el motivo por categoría y el texto original permanece intacto.

Cada llamada usa la aplicación `text_assistant`, así que aplican la allowlist, el control de cuota y el registro de consumo comunes. Nunca se registra el texto ni el prompt.

### Activación y configuración

- Variables (ver `config/lnx_ai.php`): `AI_TEXT_ASSISTANT_TIMEOUT`, `AI_TEXT_ASSISTANT_MAX_OUTPUT_TOKENS`, `AI_TEXT_ASSISTANT_MAX_INPUT_CHARS` y `AI_TEXT_ASSISTANT_LIMIT_*` para los límites propios de la función.
- Los idiomas de destino se declaran en `text_assistant.languages` (por defecto `es` y `en`).
- La pantalla de demostración se habilita con `AI_TEXT_ASSISTANT_DEMO_ENABLED=true` y queda disponible, solo para `root`, en `/admin/ai/text-assistant` (enlazada desde `/admin/ai`). Usa un campo de prueba y no toca datos de negocio.

### Extender en un proyecto derivado

Define tu habilidad (por ejemplo una policy) y pásala en `ability`; coloca `<livewire:ai.text-assistant wire:model="tuCampo" ability="tu-habilidad" />` donde estaba tu campo de texto. El componente reutiliza `App\Ai\Agents\TextAssistantAgent` y `App\Ai\TextAssistant`; para variantes nuevas declara su propia aplicación y límites y pásalos al gateway.

## Chat interactivo (conversacional, streaming y herramientas)

lnx incluye un módulo de chat interactivo conversacional en `/chat` (`chat`) accesible para cualquier cuenta autenticada. Permite crear, seleccionar, continuar y eliminar conversaciones privadas con respuestas progresivas por streaming.

### Aislamiento y privacidad

- Las conversaciones se guardan en las tablas `ai_conversations` y `ai_messages` con clave foránea ligada a la cuenta de usuario.
- `App\Policies\AiConversationPolicy` y `App\Ai\ChatService` aplican aislamiento estricto: ningún usuario puede ver, enviar mensajes ni eliminar conversaciones ajenas cambiando identificadores.

### Cuotas, modelos y streaming

- Invocado bajo la aplicación `chat` pasando por `AiGateway::stream()`. Valida que el modelo esté en la allowlist y declare la capacidad `streaming`.
- Aplica cuotas atómicas locales (`AI_CHAT_LIMIT_*`) y registra el consumo en `ai_usage_events`.

### Contexto explícito de página y herramientas de lectura

- El chat soporta recibir contexto explícito de página (`<page_context>`), sanitized en servidor sin enviar formularios ni datos sensibles.
- Incluye la herramienta de lectura `App\Ai\Tools\ProductHelpSearchTool` que re-valida la autenticación y autorización del usuario en cada ejecución.

### Extender en proyectos derivados

- Para añadir una herramienta de lectura personalizada, crea una clase que implemente `Laravel\Ai\Contracts\Tool`, re-valida `$user->can(...)` dentro de `handle()` y pásala a `ChatService::sendMessage($user, $conv, $prompt, $context, [$miHerramienta])` o configúrala en `ChatAgent::tools()`.

## Extracción de documentos con IA y revisión

lnx incluye un módulo de extracción de datos de documentos con IA en `/documents` (`documents.index`, `documents.show`). Permite subir documentos privados, procesarlos en cola con OpenRouter, validar la salida JSON contra un esquema configurable, revisar y ajustar los campos mediante una interfaz interactiva y confirmar la extracción.

### Formatos, almacenamiento y privacidad

- **Formatos soportados**: PDF (con texto seleccionable) y archivos de texto plano (`.txt`, `.md`, `.json`, `.csv`).
- **Almacenamiento privado**: Los archivos se guardan en el disco privado `storage/app/private/documents/{user_id}/`. No son accesibles directamente por URL; la descarga y vista previa están resguardadas por `DocumentExtractionPolicy` y la ruta protegida `/documents/{document}/download`.
- **PDFs escaneados sin OCR**: Los documentos escaneados sin capa de texto seleccionable se detectan y explican con un error entendible ("Documento escaneado o no legible: OCR no soportado"), sin fallos silenciosos ni OCR implícito.

### Procesamiento en cola y cuotas de IA

- La extracción se ejecuta mediante el job `App\Jobs\ProcessDocumentExtractionJob`.
- Utiliza la aplicación `document_extraction` a través de `AiGateway::prompt()`, aplicando la reserva de cuotas por usuario y aplicación (`AI_DOCUMENT_LIMIT_*`), registrando el consumo en `ai_usage_events` y manejando reintentos configurados (`$tries = 3`, `$backoff = [10, 30]`).
- Variables (ver `config/lnx_ai.php` y `.env.example`): `AI_DOCUMENT_TIMEOUT`, `AI_DOCUMENT_MAX_OUTPUT_TOKENS`, `AI_DOCUMENT_MAX_FILE_SIZE_KB`, `AI_DOCUMENT_MAX_PAGES` y el bloque `AI_DOCUMENT_LIMIT_*`.

### Revisión humana y confirmación de negocio

- **Revisión**: En `/documents/{document}` el usuario visualiza advertencias si faltan campos requeridos o hay formatos no estándar, puede editar los campos en `reviewed_data` y guardar borradores sin confirmar.
- **Confirmación**: Al pulsar "Confirmar documento", el servidor valida los datos, actualiza el estado a `confirmed` y dispara el evento de dominio `App\Events\DocumentExtractionConfirmed`.
- **Extensión en proyectos derivados**: Para integrar la extracción con modelos de negocio (por ejemplo crear facturas), escucha el evento `DocumentExtractionConfirmed` en un Listener o extiende `App\Ai\DocumentExtractor\SampleExtractionSchema` y `ExtractionValidator` con el esquema propio del dominio.

## Base de Conocimiento e Integración RAG (Consultar conocimiento propio)

lnx incluye un módulo RAG (Retrieval-Augmented Generation) en `/knowledge` (`knowledge.index`, `knowledge.show`) integrado con el chat interactivo contextual (`/chat`). Permite incorporar manuales, guías o catálogos, dividirlos en fragmentos (chunks), generar embeddings vectoriales mediante OpenRouter y responder preguntas con referencias verificables.

### Infraestructura opcional y almacenamiento vectorial

- **Almacenamiento vectorial integrado**: Los vectores y fragmentos se guardan directamente en las tablas `knowledge_documents` y `knowledge_chunks` de la base de datos de la aplicación (SQLite/PostgreSQL). No se exige una base vectorial externa (Pinecone/Qdrant/pgvector).
- **Módulo inactivo por defecto**: `AI_KNOWLEDGE_ENABLED=false` mantiene el módulo apagado por defecto. La instalación básica de SQLite funciona sin depender del módulo.

### Embeddings con OpenRouter

- Los embeddings se generan vía Laravel AI SDK (`Embeddings::for(...)`) utilizando OpenRouter con la clave del despliegue (`OPENROUTER_API_KEY`).
- Modelo predeterminado: `google/gemini-embedding-001` (configurable vía `AI_KNOWLEDGE_EMBEDDING_MODEL` dentro de la lista de modelos permitidos).

### Ingesta, reindexación y retiro de contenido

- **Formatos soportados**: Entrada directa de texto plano o carga de archivos `.txt`, `.md`, `.json`, `.csv`.
- **Reindexación idempotente**: La reindexación reemplaza atómicamente los fragmentos existentes en base de datos.
- **Retiro inmediato**: Marcar un documento como retirado (`withdrawn_at`) o eliminarlo impide inmediatamente recuperar sus fragmentos en cualquier consulta de conocimiento.

### Filtrado por autorización (Authorization-first)

- El motor de búsqueda `KnowledgeSearchService` aplica la política `KnowledgeDocumentPolicy` y el scope `accessibleTo($user)` **antes** de enviar fragmentos al LLM. Un usuario nunca puede recuperar fragmentos de documentos a los que no tiene permiso.
- Las instrucciones contenidas dentro de fragmentos de documentos se tratan estrictamente como texto pasivo de lectura, previniendo inyecciones de prompt.

### Respuestas con citas verificables e integración con Chat

- Las respuestas generadas por el chat citan expresamente las fuentes recuperadas mediante etiquetas `[Fuente: Título (Fragmento #)]`.
- Cuando las fuentes disponibles no contienen suficiente información para responder a la consulta, el asistente indica explícitamente la falta de sustento.
- Incluye la herramienta `App\Ai\Tools\KnowledgeSearchTool` accesible para agentes y chats interactivos.

### Cuotas y registro de consumo

- Las peticiones de embeddings y generación de respuestas utilizan las aplicaciones `knowledge_indexing` y `knowledge_query` a través del `AiGateway`, aplicando cuotas locales por usuario/aplicación y registrando el consumo en `ai_usage_events`.

### Evaluación manual de calidad

1. Ingesta de un manual técnico o procedimiento en `/knowledge`.
2. Verificación de que la indexación genere fragmentos coherentes.
3. Consulta en `/chat` comprobando que la respuesta incluya citas precisas a la fuente.
4. Prueba con una pregunta ajena al corpus comprobando que el asistente informe que las fuentes no sustentan la respuesta.
5. Retiro del documento comprobando la imposibilidad inmediata de recuperar sus fragmentos.

## Estrategia de ramas (Git Flow)

- `main` — producción, siempre estable.
- `develop` — integración; rama base de trabajo.
- `feature/{nombre}` — nuevos features, creadas desde `develop`.
- `fix/{nombre}` / `hotfix/{nombre}` — correcciones, creadas desde `main`.

**El workflow de documentación es obligatorio**: cada feature o corrección debe incluir la actualización de la documentación afectada antes de integrarse.

## Documentación

- `README.md` — este archivo, panorámica del proyecto.
- `.ai/rules/` — reglas del kit (`index.md`, `general.md`, `views.md`); las reglas propias del proyecto derivado se escriben en `.ai/rules/local.md` (personalización local que el kit no sobrescribe).
- `.ai/skills/` — skills propias versionadas (`implement-feature`, `expose-mcp-operation`, `verify-delivery`, `design-screen`, `create-ui-component`, `update-agent-kit`, `brand-logos`); Boost las distribuye a los clientes configurados.
- `.ai/kit-manifest.json` — manifiesto versionado del kit para agentes (versión, origen, compatibilidad y componentes mantenidos).
- `.ai/CHANGELOG.md` — registro de cambios y notas de migración del kit de agentes.
- `git-closeout` — skill global (fuera del repo) para cerrar el ciclo: documentación, commits, integración de ramas y push.

## Trabajo con agentes

Esta sección es la entrada canónica para agentes en lnx. Laravel Boost genera `AGENTS.md` para Antigravity, Codex y OpenCode, y `CLAUDE.md` para Claude Code. La directriz versionada `.ai/guidelines/project-entry.md` se incorpora a ambos formatos al ejecutar `php artisan boost:update --no-discover --no-interaction`; los archivos generados son locales y no se editan como fuente. Si un cliente necesita instrucciones propias, su adaptación solo debe apuntar a esta sección y a `.ai/rules/index.md`.

| Necesidad | Fuente o comando |
| --- | --- |
| Prioridades y próximo trabajo | [ROADMAP.md](ROADMAP.md); actualizar cuando cambien el estado de las issues, sus dependencias, bloqueos o prioridades acordadas |
| Versiones PHP instaladas | `composer show --direct` |
| Dependencias JS | `package.json` y `npm ls --depth=0` |
| Módulos, rutas y estructura | `app/`, `routes/`, `resources/`; `php artisan route:list --except-vendor` |
| Reglas por ruta | `.ai/rules/index.md`, luego los archivos indicados |
| Reglas locales del proyecto | `.ai/rules/local.md` (personalización local; el updater del kit no la sobrescribe) |
| Skills propias | `.ai/skills/` (`implement-feature`, `expose-mcp-operation`, `verify-delivery`, `design-screen`, `create-ui-component`, `update-agent-kit`, `brand-logos`); Boost las distribuye a los clientes configurados |
| Skills de paquetes instalados | `php artisan boost:list-skills --no-interaction`; pueden variar por instalación |
| Actualización del kit de agentes | `.ai/kit-manifest.json`; `python3 scripts/update-agent-kit.py --source-kit <ruta-lnx> --preview` (previsualizar) o `--apply` (aplicar) |
| MCP | Laravel Boost (`php artisan boost:mcp`) cuando el cliente lo configure; el servidor de aplicación de lectura se documenta en **Servidor MCP (lectura)** y no presupone plugins personales |
| Verificación | `php artisan test --compact`, `vendor/bin/pint --dirty --format agent`, `composer types:check`, `npm run build`, `python3 scripts/check-agent-context.py` |

Las reglas versionadas en `.ai/rules` expresan decisiones de lnx; las skills enseñan procedimientos; los comandos anteriores verifican el checkout y Boost es el servidor MCP disponible en la instalación de referencia. `boost.json`, `AGENTS.md`, `CLAUDE.md` y las copias de skills bajo carpetas de clientes son generados y están ignorados por Git. El núcleo del kit incluye los patrones de autorización, idioma, layout y tema; cada proyecto derivado decide su nombre, dominio, marca, contenido, credenciales, despliegue y cualquier regla local adicional, que escribe en `.ai/rules/local.md` sin tocar los archivos mantenidos por el kit.

Los módulos actuales son autenticación y perfil (`routes/settings.php`), usuarios e invitaciones (`routes/users.php`), actividad administrativa (`app/Livewire/Activity/`), configuración global (`app/Livewire/Settings/`), checklist de puesta en marcha (`app/Livewire/Setup/`), servidor MCP de lectura (`app/Mcp/`) y base de IA con OpenRouter (`app/Ai/`, `/admin/ai`, `/admin/ai/usage`, asistente de texto `app/Livewire/Ai/TextAssistant.php`). Las versiones y la lista de rutas se consultan con los comandos de la tabla, sin fijarlas en instrucciones generadas.

La instrucción explícita del usuario prevalece. Después se aplican las reglas del proyecto derivado para sus decisiones, las reglas de lnx para el núcleo que sigue usando y, finalmente, las recomendaciones de Boost y skills. Si dos instrucciones del mismo nivel chocan, detén solo la decisión afectada y pide una aclaración concreta; no escribas una regla nueva por inferencia. Las reglas permanentes se registran únicamente a petición explícita mediante `record-rule`.

### Contrato visual

La referencia de tokens es `resources/css/app.css`: Instrument Sans, escala de espaciado de Tailwind 4, superficies zinc y variables `--color-accent`, `--color-accent-content` y `--color-accent-foreground`. En las pantallas actuales, `flux:heading` y `flux:text` establecen la jerarquía tipográfica, `gap-4` separa acciones próximas y `mt-6` o `space-y-6` separan secciones y campos. Las seis variantes de acento y la selección claro/oscuro/sistema se implementan en `resources/views/livewire/settings/appearance.blade.php` y `resources/views/partials/head.blade.php`. El proyecto derivado puede sustituir la identidad y el acento predeterminado sin alterar los patrones de interacción del kit.

| Patrón | Implementación canónica |
| --- | --- |
| Navegación y breadcrumbs | `resources/views/layouts/app/sidebar.blade.php`, `resources/views/layouts/app.blade.php` |
| Formularios y validación | `resources/views/livewire/users/create.blade.php` y `resources/views/livewire/settings/general.blade.php` |
| Tablas, filtros y vacío | `resources/views/livewire/users/index.blade.php` |
| Diálogos y acciones | Modal de suspensión en `resources/views/livewire/users/index.blade.php` |
| Carga y error | `resources/views/design-gallery.blade.php` y `resources/views/livewire/users/invitations.blade.php` |

La galería `/design-gallery` solo se registra en `local` y `testing`, tras autenticación y con rol `root`. `DESIGN_GALLERY_ENABLED=false` la desactiva también en esos entornos; producción nunca la registra. No aparece en la navegación. Muestra estados de carga, vacío, error, validación y acciones con componentes Flux existentes. Para revisar una pantalla, comparar móvil estrecho y escritorio, temas claro/oscuro, los seis acentos, navegación por teclado y foco visible; anotar capturas y correcciones en el PR. El selector de acento en `resources/views/livewire/settings/appearance.blade.php` es el primer caso aplicado: sus botones muestran un contorno de foco explícito. Si cambia un patrón, actualizar implementación, ejemplo de galería y esta guía en el mismo cambio. La skill `.ai/skills/design-screen/SKILL.md` indica el recorrido de revisión sin duplicar estas decisiones.

### Componentes desde capturas

La skill `.ai/skills/create-ui-component/SKILL.md` guía la adaptación de una captura a un componente Blade o Livewire reutilizable. Con la captura y su propósito prepara una ficha breve de estructura, datos, acciones, estados y ancho responsivo antes de implementar. El contenedor asigna columnas de una grilla de 12; por ejemplo, una tarjeta de estadísticas puede ocupar `col-span-12 md:col-span-6 xl:col-span-3`. El componente no recibe una prop de ancho. Tras revisar y aprobar el candidato para la biblioteca, se incorpora a `/design-gallery` con las variantes pertinentes y un ejemplo de invocación. Empieza en esta aplicación; cuando la API esté estabilizada y el componente sea presentacional puro (sin modelos, rutas ni servicios del host) se extrae al paquete compartido `luinuxscl/lnx-ui` (ver «Componentes compartidos»).

La biblioteca incluye `x-stat` (`label`, `value`, `icon` Flux y `color`), `x-stat-card` (una tarjeta individual que reutiliza `x-stat` y las mismas props) y `x-statistics-panel` (`title`, `updated-at` opcional y slot de métricas). El panel usa `flux:card` y una grilla de 12 columnas; el contenedor externo asigna el ancho a cada tarjeta individual. `color` acepta `accent` (predeterminado), `violet`, `cyan`, `rose`, `emerald`, `blue` y `amber`. El consumidor aporta cifras ya formateadas y textos traducidos, sin consultas ni lógica de negocio dentro de los componentes. Cuando se entrega `updated-at`, el panel muestra un icono cuyo tooltip contiene ese texto; sin la prop, no muestra el icono.

`x-lnx-ui::trend-card` (paquete `luinuxscl/lnx-ui`) muestra una métrica con línea de tendencia y tiene ejemplos aprobados en `/design-gallery`. Recibe `label`, `value` y `change` ya formateados y traducidos, `series` como arreglo de cifras ordenadas y `color` (`accent`, `emerald`, `amber` o `rose`). Dibuja una línea decorativa si hay al menos dos puntos válidos; sin ellos mantiene el texto de la métrica. El contenedor asigna su ancho, normalmente `col-span-12 md:col-span-6 xl:col-span-4` para tres tarjetas por fila en escritorio.

`x-tool-card` es una tarjeta-enlace a un área de la aplicación y tiene un ejemplo aprobado en `/design-gallery`. Recibe `title`, `description`, `icon` (icono Flux), `href`, `color` (`accent` predeterminado y las mismas variantes que `x-stat`) y `external` (abre en una pestaña nueva con `rel="noopener noreferrer"`). Todo el bloque es un enlace; internamente navega con `wire:navigate` salvo cuando `external` es verdadero. El contenedor asigna su ancho, normalmente `col-span-12 sm:col-span-6 xl:col-span-4` (tres por fila) o `xl:col-span-3` (cuatro por fila).

El dashboard de admin usa `x-stat` dentro de las tarjetas de usuarios que incluyen acciones; el dashboard de root usa `x-tool-card` para enlazar las herramientas de root y las de la app. Los estados vacíos y los enlaces siguen en la pantalla; `x-lnx-ui::trend-card` se reserva para datos que tengan una serie histórica real.

```blade
<x-statistics-panel :title="__('Statistics')" :updated-at="__('Updated 1 month ago')">
    <x-stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" color="violet" />
    <x-stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Customers')" value="8.549k" icon="users" color="cyan" />
</x-statistics-panel>

<div class="grid grid-cols-12 gap-4">
    <x-stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" color="violet" />
    <x-stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Customers')" value="8.549k" icon="users" color="cyan" />
</div>
```

```blade
<div class="grid grid-cols-12 gap-4">
    <x-lnx-ui::trend-card
        class="col-span-12 md:col-span-6 xl:col-span-4"
        :label="__('Product sold')"
        value="2.968"
        :change="__(':change last week', ['change' => '+3.2%'])"
        :series="[28, 16, 34, 42, 51, 75, 78, 80, 72, 21]"
        color="emerald"
    />
</div>
```

`resources/views/design-gallery.blade.php` sitúa estos componentes al inicio: tarjetas de tendencia, tarjetas individuales, el panel con cuatro métricas y la variante sin fecha de actualización. Las decisiones visuales reutilizables figuran en `.ai/skills/create-ui-component/references/style-foundations.md`.

Los estilos reutilizables aprobados se registran en `.ai/skills/create-ui-component/references/style-foundations.md`: espaciado, fuente, jerarquía de títulos, superficies y foco. `resources/css/app.css` sigue siendo la fuente de tokens CSS. Después de incorporar un componente se puede abrir una revisión de la skill si el trabajo reveló una mejora generalizable; las particularidades de una captura quedan en el componente.

### Componentes compartidos (paquete `luinuxscl/lnx-ui`)

Los componentes presentacionales reutilizables viven en el paquete Composer privado `luinuxscl/lnx-ui`, fuente única para lnx y sus apps derivadas. Un componente es candidato a migrar cuando no consulta la base de datos ni lee modelos, rutas o servicios del host; los acoplados (`app-logo`, `user-avatar`, `desktop-user-menu`, `dev-credentials-warning`, `settings/layout`) se quedan en `resources/views/components/` de cada app.

En cada aplicación:

- `composer.json`: repositorio VCS privado (`git@github.com:luinuxscl/lnx-ui.git`) y `composer require luinuxscl/lnx-ui`. Para desarrollo local en vivo, superpón temporalmente un repositorio `path` a `../lnx-ui`. El `LnxUiServiceProvider` se autodescubre (`extra.laravel.providers`); no hay que registrarlo a mano. CI y despliegue necesitan acceso al repositorio privado (deploy key o token).
- `resources/css/app.css`: `@source '../../vendor/luinuxscl/lnx-ui/resources/views';`, porque Tailwind 4 no escanea `vendor/`.
- Consumo con namespace: `<x-lnx-ui::trend-card ... />`.
- Override de un componente: `resources/views/vendor/lnx-ui/components/*.blade.php`; Laravel lo resuelve antes que el paquete. `php artisan vendor:publish --tag=lnx-ui-views` publica todas las vistas para copiarlas.

`x-lnx-ui::trend-card` es el primer componente migrado; conserva la API y los ejemplos que tenía `x-trend-card`.

La skill usa `tailwind-design-system` de [wshobson/agents](https://github.com/wshobson/agents), fijada a una revisión en `skills-lock.json`. En un checkout nuevo, restáurala con `npx skills experimental_install` y sincroniza las skills propias con `php artisan boost:update --no-discover --no-interaction`.

### Logos de marcas (SVGL)

Los logos de marcas (tecnologías, proveedores y servicios) se obtienen de [SVGL](https://svgl.app), biblioteca open source (MIT) con API REST pública, gratuita y sin autenticación. La vía autorizada es esa API más el **vendorizado local**: el SVG se descarga durante el desarrollo, se guarda en el repositorio y la aplicación nunca llama a SVGL en runtime. Queda descartado el MCP comunitario `svgl-mcp` (sus herramientas de búsqueda están detrás de pago) y el registry de shadcn/ui (genera componentes React, ajenos a Blade/Livewire).

La skill `.ai/skills/brand-logos/SKILL.md` describe la búsqueda, la elección de variantes light/dark, la normalización del SVG, la licencia y la verificación. Los logos viven como componentes Blade anónimos en `resources/views/components/brand-logos/*.blade.php` y se consumen con el envoltorio `x-brand-logo` (o el parcial directo). SVGL se reserva para marcas; la iconografía de interfaz sigue usando Flux/Heroicons.

### Coherencia de instrucciones

`python3 scripts/check-agent-context.py` comprueba de forma determinista enlaces locales mantenidos, el índice de reglas, la estructura mínima de skills propias y los comandos marcados como verificables. Se ejecuta localmente y en CI sin claves ni LLM. Las referencias externas, las carpetas ignoradas/generadas y los ejemplos no marcados quedan fuera. El check informa archivo, causa y corrección sugerida, y falla ante errores objetivos; las contradicciones semánticas requieren revisión humana. En cada PR que cambie comportamiento o diseño, revisar si hay que actualizar reglas existentes, skills, ejemplos, traducciones y esta guía. Los agentes pueden proponer esas actualizaciones; una regla permanente nueva sigue necesitando solicitud explícita y `record-rule`.

## Estructura clave

```
app/Livewire/Settings/            Componentes de settings (incl. General de root)
app/Mcp/                          Servidor MCP de lectura, herramientas y capacidades
app/Ai/                           Base de IA (config, gateway, cuota, registro de consumo, agentes)
app/Ai/Agents/TextAssistantAgent.php  Agente del asistente de texto
app/Ai/TextAssistant.php          Asistente de texto (validación, prompt y resultado seguro)
app/Livewire/Ai/                  Estado, prueba de conexión, panel de consumo y asistente de texto (root)
app/Livewire/Ai/TextAssistantDemo.php  Pantalla de demostración del asistente (root, activable)
app/Livewire/Setup/               Checklist de puesta en marcha (root)
app/Livewire/Users/               CRUD de usuarios (root/admin)
app/Models/Setting.php            Configuración global cacheada (fila única)
app/Models/AiUsageEvent.php       Registro de consumo de IA (un intento por fila)
app/Models/AiQuotaCounter.php     Contadores de cuota por ventana (reserva atómica)
config/lnx_ai.php                 Configuración de IA (activación, modelo, allowlist, límites, tarifas)
app/Policies/                     Policies (UserPolicy, SettingPolicy)
app/Http/Middleware/              SetLocale, ApplySiteSettings, CheckMaintenanceMode
resources/views/layouts/          Layouts (app, auth)
resources/views/errors/           Vistas de error (maintenance)
resources/css/app.css             Tokens de Tailwind + theming Flux
.kilo/agent/                      Sub-agentes de Kilo (ui-audit: auditoría visual de vistas con modelo económico de visión)
tests/                            Suite de Pest
```
