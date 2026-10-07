---
paths:
  - 'resources/views/**'
---

# Views

## Vistas siempre bilingües (español por defecto)
La app es bilingüe es/en y el español es el idioma por defecto. Toda vista nueva o modificada debe incluir ambos idiomas: envuelve todo texto visible en __() y agrega la clave con su traducción en lang/es.json y lang/en.json (estrategia "translation strings as keys"). No preguntar: crear las traducciones siempre. Los títulos de página se traducen con __($title) en resources/views/partials/head.blade.php, así que los #[Title] deben usar la cadena fuente en inglés. Usa el componente livewire:locale-switcher para el cambio de idioma en tiempo real.

## Preservación de anglicismos estándar de la industria
Los términos y acrónimos del ámbito tecnológico que constituyen estándares aceptados en la industria (tales como `Dashboard`, `Passkeys`, `Chatbot`, `RAG`, `2FA`, `MCP`, `API`, `Tokens`) no deben traducirse literalmente al español en los menús ni en la interfaz de usuario. Deben conservarse en su forma estándar original tanto en `lang/es.json` como en `lang/en.json`.


## Sidebar: zonas y prioridad por rol
El sidebar (`resources/views/layouts/app/sidebar.blade.php`) respeta la visibilidad por rol con `App\Enums\Role` (nunca strings). `Dashboard` (`home`) es **siempre el primer ítem**. `Messages` ya no vive aquí: es un botón en el header junto a las notificaciones (`resources/views/layouts/app.blade.php` en desktop y el header móvil del sidebar).

- **root**: tras `Dashboard`, el grupo `Administration` (`cog-6-tooth`) con sus herramientas: Soporte (`inbox`, etiqueta `Support`), Usuarios (`users`), Actividad (`clock`), Ajustes (`cog`), Checklist (`clipboard-document-check`) e IA (`sparkles`). Después, el grupo `AI Tools` (`sparkles`) con Chat (`chat-bubble-left-right`), RAG (`book-open`) y Document AI (`document-text`). Root no muestra los ítems sin destino `Repository` ni `Documentation`.
- **admin y usuarios**: tras `Dashboard`, el grupo `AI Tools`. Después del `flux:spacer`, el grupo `Administration` (`cog-6-tooth`) es visible solo para `admin` y contiene `Users` (`users`) y `Documentation` (`book-open-text`); `Documentation` aún no tiene destino.
- **Personal del usuario** (anclado abajo): componente `x-desktop-user-menu` (avatar, nombre, ajustes, cerrar sesión). No añadir menús de app ni de configuración aquí.

## Componentes UI compartidos (paquete `luinuxscl/lnx-ui`)
Por defecto, todo componente visual reutilizable nuevo se crea en el paquete Composer privado `luinuxscl/lnx-ui`, no en la app: es la fuente única para lnx y sus apps derivadas. Solo se crean en `resources/views/components/` de la app los componentes muy específicos de su dominio o acoplados al host (modelos, rutas o servicios): por ejemplo `app-logo`, `user-avatar`, `desktop-user-menu`, `dev-credentials-warning`, `settings/layout`. Los componentes del paquete se consumen con su namespace (`<x-lnx-ui::trend-card ... />`) y se sobrescriben creando la vista en `resources/views/vendor/lnx-ui/components/*.blade.php` (Laravel la resuelve antes que el paquete); nunca edites `vendor/`.
El catálogo de la galería vive en el paquete (vista `lnx-ui::design-gallery`): agrupa los componentes por tipo y da a cada uno un nombre y un ancla estable (`#<nombre>`) para poder referenciarlo con la IA (p. ej. «ajusta el componente `trend-card`»). La app solo aporta el layout, la ruta y el gating en `resources/views/design-gallery.blade.php`, que incluye `@include('lnx-ui::design-gallery')`. Al añadir o cambiar un componente, actualiza su ejemplo en la vista del paquete.
El paquete se desarrolla en el clon local registrado en `.ai/rules/local.md`; el alta del paquete en una app (repositorio en `composer.json` y `@source` en `resources/css/app.css`) figura en las reglas generales y en el README.
