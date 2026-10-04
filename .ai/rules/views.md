---
paths:
  - 'resources/views/**'
---

# Views

## Vistas siempre bilingües (español por defecto)
La app es bilingüe es/en y el español es el idioma por defecto. Toda vista nueva o modificada debe incluir ambos idiomas: envuelve todo texto visible en __() y agrega la clave con su traducción en lang/es.json y lang/en.json (estrategia "translation strings as keys"). No preguntar: crear las traducciones siempre. Los títulos de página se traducen con __($title) en resources/views/partials/head.blade.php, así que los #[Title] deben usar la cadena fuente en inglés. Usa el componente livewire:locale-switcher para el cambio de idioma en tiempo real.

## Preservación de anglicismos estándar de la industria
Los términos y acrónimos del ámbito tecnológico que constituyen estándares aceptados en la industria (tales como `Dashboard`, `Passkeys`, `Chatbot`, `RAG`, `2FA`, `MCP`, `API`, `Tokens`) no deben traducirse literalmente al español en los menús ni en la interfaz de usuario. Deben conservarse en su forma estándar original tanto en `lang/es.json` como en `lang/en.json`.


## Sidebar: tres zonas y visibilidad por rol
El sidebar (`resources/views/layouts/app/sidebar.blade.php`) se organiza en tres zonas, de arriba a abajo. Al generar un menú nuevo, colocarlo en la zona que corresponda y respetar la visibilidad por rol con `App\Enums\Role` (nunca strings).

1. Menú de app (nav superior): solo menús del funcionamiento de la web app. Incluye el ítem principal `Dashboard` (`home`) y el grupo de dominio desplegable `AI Tools` (`sparkles`) que contiene las herramientas de IA del kit: Chat (`chat-bubble-left-right`), RAG (`book-open`) y Document AI (`document-text`).
2. Menú de configuración y administración (`flux:sidebar.nav` tras el `flux:spacer`): visible para root y admin, agrupado en `Administration` (`cog-6-tooth`). Usuarios (`users`) enlaza al CRUD de usuarios (`admin.users.index`) y lo ven root y admin. Actividad (`clock`) enlaza a `admin.activity.index` y es visible solo para root. Configuración (`cog`) enlaza a la vista de configuración global (`admin.settings.edit`) y es visible solo para root. Checklist (`clipboard-document-check`) enlaza a `admin.setup.index` y es visible solo para root. IA (`sparkles`) enlaza a `admin.ai.index` y es visible solo para root. Repositorio (`folder-git-2`) apunta al repo de esta app `https://github.com/luinuxscl/lnx` y es visible solo para root. Documentación (`book-open-text`) es visible para root y admin, y aún no tiene destino (sin `href`, se renderiza como botón).
3. Personal del usuario (anclado abajo): componente `x-desktop-user-menu` (avatar, nombre, ajustes, cerrar sesión). No añadir menús de app ni de configuración aquí.
