---
paths:
  - 'resources/views/**'
---

# Views

## Vistas siempre bilingües (español por defecto)
La app es bilingüe es/en y el español es el idioma por defecto. Toda vista nueva o modificada debe incluir ambos idiomas: envuelve todo texto visible en __() y agrega la clave con su traducción en lang/es.json y lang/en.json (estrategia "translation strings as keys"). No preguntar: crear las traducciones siempre. Los títulos de página se traducen con __($title) en resources/views/partials/head.blade.php, así que los #[Title] deben usar la cadena fuente en inglés. Usa el componente livewire:locale-switcher para el cambio de idioma en tiempo real.

## Preservación de anglicismos estándar de la industria
Los términos y acrónimos del ámbito tecnológico que constituyen estándares aceptados en la industria (tales como `Dashboard`, `Passkeys`, `Chatbot`, `RAG`, `2FA`, `MCP`, `API`, `Tokens`) no deben traducirse literalmente al español en los menús ni en la interfaz de usuario. Deben conservarse en su forma estándar original tanto en `lang/es.json` como en `lang/en.json`.


## Sidebar: menú gestionado por root
El sidebar (`resources/views/layouts/app/sidebar.blade.php`) ya no está hardcodeado: se renderiza desde `App\Support\Navigation\MenuResolver`, que resuelve el menú de la audiencia del usuario con `App\Enums\NavigationAudience` (root, admin o usuario sin rol; gana la de mayor jerarquía, sin herencia). El parcial `resources/views/partials/navigation-sidebar.blade.php` pinta grupos e ítems, y `partials/navigation-sidebar-item.blade.php` cada ítem. `Messages` sigue fuera del sidebar: es un botón del header junto a las notificaciones. Los grupos con encabezado arrancan **colapsados**; solo se abre automáticamente el grupo que contiene la ruta actual.

- **Fuente de verdad**: las tablas `navigation_menus` / `navigation_groups` / `navigation_items`, sembradas por `Database\Seeders\NavigationSeeder` con los valores por defecto (equivalentes al sidebar anterior). Si no existe la fila del menú de una audiencia, el resolver cae a las rutas etiquetadas con la macro `->menu()` para no dejar el sidebar vacío.
- **Etiquetado de rutas**: lnx y los packages anotan sus rutas con `->menu([...])` (label como clave de traducción, `icon`, grupo sugerido `group`/`group_icon`, `audiences` sugeridas y `roles` permitidos). `MenuResolver` filtra los ítems que el usuario no puede usar además del middleware de la ruta: la visibilidad del menú es solo presentación y nunca concede acceso.
- **Gestión**: `/admin/navigation` (Livewire `App\Livewire\Navigation\Index`, solo root) permite, por audiencia, crear/editar/reordenar grupos e ítems, activarlos u ocultarlos y añadir rutas internas (etiquetadas o en modo avanzado) o enlaces externos validados con `App\Rules\SafeNavigationUrl`.
- **Etiquetas**: los ítems de ruta toman etiqueta e icono en vivo de la etiqueta de la ruta; los ítems de enlace guardan etiqueta es/en obligatoria. Los grupos sembrados usan clave de traducción; los creados por root, es/en. Todo cambio se registra en Actividad administrativa.
- **Personal del usuario** (anclado abajo): componente `x-desktop-user-menu` (avatar, nombre, ajustes, cerrar sesión). No añadir menús de app ni de configuración aquí.

## Componentes UI compartidos (paquete `luinuxscl/lnx-ui`)
Por defecto, todo componente visual reutilizable nuevo se crea en el paquete Composer privado `luinuxscl/lnx-ui`, no en la app: es la fuente única para lnx y sus apps derivadas. Solo se crean en `resources/views/components/` de la app los componentes muy específicos de su dominio o acoplados al host (modelos, rutas o servicios): por ejemplo `app-logo`, `user-avatar`, `desktop-user-menu`, `dev-credentials-warning`, `settings/layout`. Los componentes del paquete se consumen con su namespace (`<x-lnx-ui::trend-card ... />`) y se sobrescriben creando la vista en `resources/views/vendor/lnx-ui/components/*.blade.php` (Laravel la resuelve antes que el paquete); nunca edites `vendor/`.
El catálogo de la galería vive en el paquete (vista `lnx-ui::design-gallery`): agrupa los componentes por tipo y da a cada uno un nombre y un ancla estable (`#<nombre>`) para poder referenciarlo con la IA (p. ej. «ajusta el componente `trend-card`»). La app solo aporta el layout, la ruta y el gating: la página `admin/packages/lnx-ui` (Livewire `App\Livewire\Packages\LnxUi`, rol root) muestra la versión instalada y la última disponible del paquete y, debajo, incluye la galería con `@include('lnx-ui::design-gallery')`. Al añadir o cambiar un componente, actualiza su ejemplo en la vista del paquete.
El paquete se desarrolla en el clon local registrado en `.ai/rules/local.md`; el alta del paquete en una app (repositorio en `composer.json` y `@source` en `resources/css/app.css`) figura en las reglas generales y en el README.
