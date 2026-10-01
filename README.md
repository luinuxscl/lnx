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

La página **Actividad administrativa** (`/admin/activity`) está disponible exclusivamente para `root`. Muestra actor, fecha, acción y recurso, permite desplegar valores anteriores/nuevos y combina filtros por actor histórico, acción y días desde/hasta. Los filtros se conservan en la URL; la página muestra 10 registros por vez. Las fechas se interpretan en `config('app.timezone')` (UTC por defecto) y abarcan los días completos.

Se registran las altas por invitación, ediciones, cambios de rol, cambios administrativos de contraseña (solo el hecho), eliminaciones, suspensiones y reactivaciones de usuarios; cambios de configuración e imágenes; y creación, renovación, revocación, aceptación y resultado de envío de invitaciones. Los registros conservan identificadores y copias del nombre y correo del actor y recurso aunque se eliminen. La aceptación se atribuye al destinatario y el alta queda vinculada con su invitación. El historial no contiene contraseñas, hashes de tokens, enlaces de aceptación, secretos 2FA, credenciales de passkeys ni excepciones; para imágenes solo conserva rutas relativas.

Al desplegar, ejecutar `php artisan migrate --force` antes de servir el código nuevo. La migración permite borrar a quien emitió una invitación sin borrar la invitación. El historial comienza con la instalación de esta función: no reconstruye eventos anteriores, no tiene purga automática ni exportación. Quedan fuera el registro público, el perfil personal, accesos, seeders y cambios manuales de consola.

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

## Layout de la aplicación

El layout activo es `resources/views/layouts/app.blade.php` → `layouts/app/sidebar.blade.php`.

- **Sidebar colapsable** (desktop): el `<flux:sidebar collapsible>` se colapsa a barra de iconos. El estado se persiste en `localStorage['flux-sidebar-collapsed-desktop']` (gestionado por Flux). `resources/views/partials/head.blade.php` siembra el valor `true` en la primera visita, por lo que arranca **colapsado por defecto**; cada toggle posterior lo sobrescribe. En móvil conserva el overlay.
- **Botón de colapso en el sidebar**: `<flux:sidebar.collapse>` en el header del sidebar (patrón oficial de Flux). Al colapsar, el logo se oculta al hacer hover y es sustituido por el botón de expansión; por eso el botón debe estar presente en desktop (no usar `lg:hidden`).
- **Marca del sidebar**: junto al logo, `x-app-logo` muestra el título en negrita y, si existe, `site_tagline` debajo en texto más pequeño. Sin lema, solo se muestra el título; si falta el título configurado, se usa `APP_NAME`. Los textos largos se truncan para dejar disponible el botón de colapso.
- **Header de contenido** (solo desktop, `max-lg:hidden`): se renderiza dentro de `<flux:main>` en `layouts/app.blade.php` con el breadcrumb (`flux:breadcrumbs`) a la izquierda y, a la derecha, el botón de modo claro/oscuro (`$flux.dark`) y el selector de idioma. El colapso del sidebar se controla solo desde su propio botón en el header del sidebar.
- **Breadcrumbs por página**: cada página pasa un arreglo `['label' => string, 'href' => string|null]`; el último item sin `href` es la página actual. En Blade: `<x-layouts::app :breadcrumbs="[...]">`; en componentes Livewire full-page, `render()` devuelve `view(...)->layout('layouts.app', ['breadcrumbs' => [...]])`.
- **Alto del contenido**: `<flux:main>` es una columna flex (`flex flex-col`) para que el header de contenido y los banners convivan con la página. Una página que debe ocupar el alto restante usa `flex-1` en su raíz, **nunca `h-full`**: `h-full` reclama el 100% del alto de `main` y, al sumarse el header/banner, desborda la fila del grid; ese desborde impide que la fila crezca y rompe el sidebar sticky (el sidebar deja de cubrir todo el alto y aparece un hueco al hacer scroll).

## Avatares de usuario

Cada usuario puede subir, reemplazar o quitar su avatar en **Ajustes → Perfil**. Se aceptan JPEG, PNG y WebP de hasta 2 MB. La imagen subida tiene prioridad; si no existe o no se puede cargar, se intenta obtener el Gravatar del correo del usuario. Si Gravatar tampoco ofrece una imagen, se muestran las iniciales actuales.

Los avatares se guardan en el disco `public` (`storage/app/public/avatars`). La instalación y el despliegue deben mantener el enlace creado por `php artisan storage:link`. Al reemplazar o quitar un avatar, o al eliminar la cuenta, se borra el archivo anterior.

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

Las cuentas `root` no se pueden editar ni eliminar desde la gestión de usuarios. Nadie puede eliminar su propia cuenta. El ítem «Usuarios» vive en la zona de configuración del sidebar (ver `.ai/rules/views.md`).

### Modalidad de registro

En **Configuración general**, root puede elegir **Registro abierto** o **Solo invitaciones**. El valor inicial es registro abierto, también en instalaciones existentes que todavía no tienen una fila de configuración. En «Solo invitaciones» se ocultan los enlaces de registro público y tanto GET como POST de `/register` responden 403; los enlaces de invitación válidos siguen funcionando. El cambio se aplica al guardar, sin reconstruir la caché de rutas.

## Configuración general (General Settings)

Vista de configuración global del sitio en `/admin/settings` (`admin.settings.edit`), accesible **solo para `root`** mediante el middleware `role:root` (`routes/settings.php`). El ítem "General settings" vive en la zona de configuración del sidebar y solo lo ve root.

- `App\Livewire\Settings\General` + `App\Policies\SettingPolicy` (view/update solo root) gobiernan la vista.
- La configuración se guarda en una fila única de la tabla `settings` (`App\Models\Setting`). `Setting::current()` la cachea bajo la clave `settings` y `Setting::forgetCache()` la invalida al guardar.

### Campos configurables

| Sección | Campo | Efecto |
|---|---|---|
| Identidad | Title | `config('app.name')`, `<title>`, marca del sidebar. |
| Identidad | Tagline | Lema del sitio bajo el título del sidebar (y fallback de la meta descripción). |
| Identidad | Meta description | `<meta name="description">` (fallback: Tagline). |
| Identidad | Administration email | `config('mail.from.address')`. |
| Personalización | Logo / Dark mode logo | Logo del encabezado y sidebar; las imágenes personalizadas se muestran sobre un fondo transparente y el icono predeterminado conserva su fondo. |
| Personalización | Favicon / Apple touch icon | Iconos del `<head>`. |
| Avanzado | Footer text | Texto mostrado en el footer del contenido de las páginas autenticadas. |
| Avanzado | Analytics ID | ID de GA4 inyectado en el `<head>` (solo en producción). |
| Avanzado | Maintenance mode + message | Bloquea la app con una página 503; root y las rutas de auth siguen accesibles. |

### Cómo se aplica

- El footer del contenido muestra siempre la marca Like Innovación. Solo `admin` y `root` ven la versión de LNX, definida con `APP_VERSION` (valor predeterminado `0.1.0`).
- `App\Http\Middleware\ApplySiteSettings` (grupo `web`) vuelca los settings en `config()` y comparte `$site` con todas las vistas; `resources/views/partials/head.blade.php`, `resources/views/components/app-logo.blade.php` y el sidebar lo consumen.
- `App\Http\Middleware\CheckMaintenanceMode` (grupo `web`) devuelve la vista `errors/maintenance.blade.php` (503) cuando el modo mantenimiento está activo. Los usuarios root y las rutas de autenticación quedan exentos para poder desactivarlo.
- Los archivos de branding se suben al disco `public` (`storage/app/public/branding`); por eso `php artisan storage:link` es obligatorio en instalación y despliegue.

## Checklist de puesta en marcha

Vista en `/admin/setup` (`admin.setup.index`), accesible **solo para `root`** mediante el middleware `role:root` (`routes/web.php`). `App\Livewire\Setup\Index` muestra cuatro pasos para verificar que la instalación quedó utilizable:

1. **Identidad del sitio**: verificado cuando hay `site_title` y un `admin_email` con formato válido.
2. **Correo de prueba**: botón que envía una notificación on-demand a `admin_email` (o al correo del root si no hay ninguno) y persiste el resultado en la fila `settings`. Limitado a cinco intentos por hora y por usuario; los fallos cuentan. Un éxito solo significa que el transporte aceptó el mensaje, nunca que llegó al buzón. Un fallo de transporte se registra con `report()` y jamás expone el mensaje de la excepción (puede filtrar credenciales).
3. **Almacenamiento**: diagnóstico en vivo del enlace `public/storage` y de una escritura/lectura/borrado de prueba en el disco `public`; el archivo temporal se elimina siempre. Se puede re-ejecutar con «Re-check storage».
4. **Modalidad de registro**: verificado cuando ya existe una fila persistida en `settings`, es decir, root guardó configuración al menos una vez.

El estado de cada paso se recalcula al cargar la página desde el estado real del sistema; solo el resultado del correo de prueba se persiste (`test_email_sent_at`, `test_email_sent_to`, `test_email_failed_at` en `settings`). Si `admin_email` cambia desde el último envío exitoso, el paso vuelve a pendiente. En despliegue, la migración de esas columnas se aplica con `php artisan migrate --force`.

## Estrategia de ramas (Git Flow)

- `main` — producción, siempre estable.
- `develop` — integración; rama base de trabajo.
- `feature/{nombre}` — nuevos features, creadas desde `develop`.
- `fix/{nombre}` / `hotfix/{nombre}` — correcciones, creadas desde `main`.

**El workflow de documentación es obligatorio**: cada feature o corrección debe incluir la actualización de la documentación afectada antes de integrarse.

## Documentación

- `README.md` — este archivo, panorámica del proyecto.
- `.ai/rules/` — reglas permanentes del proyecto (git flow, documentación).
- `.ai/skills/` — skills propias versionadas; Boost instala además skills de paquetes en carpetas locales de cada cliente.
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
| Skills propias | `.ai/skills/`; Boost las distribuye a los clientes configurados |
| Skills de paquetes instalados | `php artisan boost:list-skills --no-interaction`; pueden variar por instalación |
| MCP | `php artisan boost:mcp` (Laravel Boost) cuando el cliente lo configure; no presupone plugins personales |
| Verificación | `php artisan test --compact`, `vendor/bin/pint --dirty --format agent`, `composer types:check`, `npm run build`, `python3 scripts/check-agent-context.py` |

Las reglas versionadas en `.ai/rules` expresan decisiones de lnx; las skills enseñan procedimientos; los comandos anteriores verifican el checkout y Boost es el servidor MCP disponible en la instalación de referencia. `boost.json`, `AGENTS.md`, `CLAUDE.md` y las copias de skills bajo carpetas de clientes son generados y están ignorados por Git. El núcleo del kit incluye los patrones de autorización, idioma, layout y tema; cada proyecto derivado decide su nombre, dominio, marca, contenido, credenciales, despliegue y cualquier regla local adicional.

Los módulos actuales son autenticación y perfil (`routes/settings.php`), usuarios e invitaciones (`routes/users.php`), actividad administrativa (`app/Livewire/Activity/`), configuración global (`app/Livewire/Settings/`) y checklist de puesta en marcha (`app/Livewire/Setup/`). Las versiones y la lista de rutas se consultan con los comandos de la tabla, sin fijarlas en instrucciones generadas.

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

### Coherencia de instrucciones

`python3 scripts/check-agent-context.py` comprueba de forma determinista enlaces locales mantenidos, el índice de reglas, la estructura mínima de skills propias y los comandos marcados como verificables. Se ejecuta localmente y en CI sin claves ni LLM. Las referencias externas, las carpetas ignoradas/generadas y los ejemplos no marcados quedan fuera. El check informa archivo, causa y corrección sugerida, y falla ante errores objetivos; las contradicciones semánticas requieren revisión humana. En cada PR que cambie comportamiento o diseño, revisar si hay que actualizar reglas existentes, skills, ejemplos, traducciones y esta guía. Los agentes pueden proponer esas actualizaciones; una regla permanente nueva sigue necesitando solicitud explícita y `record-rule`.

## Estructura clave

```
app/Livewire/Settings/            Componentes de settings (incl. General de root)
app/Livewire/Setup/               Checklist de puesta en marcha (root)
app/Livewire/Users/               CRUD de usuarios (root/admin)
app/Models/Setting.php            Configuración global cacheada (fila única)
app/Policies/                     Policies (UserPolicy, SettingPolicy)
app/Http/Middleware/              SetLocale, ApplySiteSettings, CheckMaintenanceMode
resources/views/layouts/          Layouts (app, auth)
resources/views/errors/           Vistas de error (maintenance)
resources/css/app.css             Tokens de Tailwind + theming Flux
.kilo/agent/                      Sub-agentes de Kilo (ui-audit: auditoría visual de vistas con modelo económico de visión)
tests/                            Suite de Pest
```
