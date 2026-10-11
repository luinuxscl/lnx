# Registro de cambios del kit para agentes (Agent Kit Changelog)

Todas las modificaciones notables al kit para agentes se documentarán en este archivo.

## [1.15.0] - 2026-10-10

### Añadido
- **Contrato de módulo — escritura por MCP declarable (Etapa 10, #39):** el servidor MCP sigue siendo de solo lectura por defecto, pero un paquete puede declarar herramientas de escritura extendiendo `App\Mcp\Tools\WriteTool` y anotándolas con `#[WritesData(resource, operation, permission, idempotent)]`. El kit provee el interruptor de despliegue `MCP_SERVER_ALLOW_WRITES` (apagado por defecto), límite de frecuencia propio (`MCP_SERVER_WRITE_RATE_LIMIT`), idempotencia por `idempotency_key` y auditoría con diff en `mcp_write_operations` (`MCP_SERVER_AUDIT_DIFF`). Las herramientas se registran con `App\Mcp\McpToolRegistry` y `LnxServer` deja de mantener su lista fija. Una herramienta que escribe sin declararse falla en `php artisan mcp:verify-writes`. Documentado en `CONTRATO-MODULO.md` §4, `README.md` y la skill `expose-mcp-operation`.

## [1.14.0] - 2026-10-10

### Añadido
- **Contrato de módulo — aplicaciones de IA por paquete (Etapa 10, #39):** un paquete declara sus aplicaciones de IA, cada una con su cuota, en el `boot()` de su provider con `App\Ai\AiApplicationRegistry::register()`. `AiConfig` resuelve la cuota declarada (con precedencia de `config/lnx_ai.php` y respaldo en `limits.default`) y `AiQuota` limita y registra el consumo bajo esa aplicación. El nombre debe ir prefijado con el paquete (`luz.extraction`); registrar uno sin prefijo es un error detectable. Las aplicaciones declaradas aparecen en el panel `/admin/ai`. Documentado en `CONTRATO-MODULO.md` §3.2 y en `README.md`.

## [1.13.0] - 2026-10-10

### Añadido
- **Contrato de módulo — permisos por paquete (Etapa 10, #39):** un paquete declara sus propios permisos granulares en el `boot()` de su provider con `App\Support\Permissions\PermissionRegistry::register()`. El comando `permissions:sync` los materializa de forma idempotente (`findOrCreate`) y los concede al rol `admin`; `root` los omite vía `Gate::before` y `RolePermissionSeeder` ejecuta el mismo sync, así que `migrate --seed` deja los permisos listos. Los nombres deben llevar el prefijo del paquete (`luz.view_patient`); registrar uno sin prefijo es un error detectable. Documentado en `CONTRATO-MODULO.md` §3.1 y en `README.md`.

## [1.12.0] - 2026-10-07

### Añadido
- Skill `lnx-ui-release` (`.ai/skills/lnx-ui-release/SKILL.md`): publica el paquete `luinuxscl/lnx-ui` — verifica, commitea en `develop`, mergea a `main`, crea el tag SemVer anotado y sube todo a `origin` — y ofrece actualizar los proyectos que lo requieren, siempre preguntando la lista. Su fuente canónica vive en el propio paquete (`resources/boost/skills/lnx-ui-release/SKILL.md`), que Boost distribuye a cada instancia; esta copia se mantiene en sincronía para la propagación vía `update-agent-kit`.

## [1.11.0] - 2026-10-07

### Añadido
- Página `admin/packages/lnx-ui` (Livewire `App\Livewire\Packages\LnxUi`, rol `root`, disponible en todos los entornos; grupo `Packages` → ítem `UI/UX components` del sidebar): muestra la versión instalada y la última disponible del paquete `luinuxscl/lnx-ui`, busca actualizaciones contra la API de GitHub (`GITHUB_TOKEN`) y ofrece el comando de actualización; debajo incluye la galería de diseño.
- Config `config/lnx_ui.php` (`repository`, `token`, `check_ttl`) y las variables `GITHUB_TOKEN`/`LNX_UI_REPOSITORY` en `.env.example`.

### Cambiado
- La ruta `design-gallery` (solo `local`/`testing`) se reemplaza por `admin/packages/lnx-ui` en todos los entornos; se elimina `resources/views/design-gallery.blade.php`. La página se nombra por el paquete (`lnx-ui`), no con un término genérico, y el menú se agrupa en `Packages`. `DESIGN_GALLERY_ENABLED` ahora oculta solo la galería dentro de la página. Reglas de vistas y README de lnx actualizados.

## [1.10.0] - 2026-10-07

### Mejorado
- Componente `x-lnx-ui::statistics-panel` (paquete `lnx-ui` v1.2.0): título `level="2"` con `size="lg"`; `updated-at` pasa a `updated-label` y se añade `updated-icon`; nuevos slots `actions`, `description` y `empty`; la grilla interna usa `gap-4` y la tarjeta se marca con `role="group"` + `aria-label`. Se actualizan el ejemplo de `/design-gallery`, el README de lnx y `.ai/skills/create-ui-component/references/style-foundations.md`.

## [1.9.0] - 2026-10-07

### Mejorado
- La galería de diseño vive en el paquete (`lnx-ui::design-gallery`): el catálogo por tipo, con nombre y ancla de cada componente, se mantiene en el paquete y las apps solo aportan layout, ruta y gating. Reglas y skill `create-ui-component` actualizadas para crear los componentes en el paquete y catalogarlos en esa vista.

## [1.8.0] - 2026-10-07

### Añadido
- Regla por defecto: todo componente visual reutilizable nuevo se crea en el paquete `luinuxscl/lnx-ui`; solo los muy específicos de su dominio o acoplados al host se crean en la app (`.ai/rules/views.md`, `.ai/rules/general.md`, skill `create-ui-component`).
- La carpeta de desarrollo en vivo del paquete se registra en `.ai/rules/local.md` de cada proyecto, para que los agentes del entorno local sepan dónde editar los componentes.
- La galería `/design-gallery` cataloga los componentes por tipo, con un nombre y un ancla estable (`#<nombre>`) para poder referenciarlos con la IA.

## [1.7.0] - 2026-10-07

### Añadido
- Migrados al paquete `luinuxscl/lnx-ui` (ahora v1.0.0) todos los componentes presentacionales compartidos: `stat`, `stat-card`, `statistics-panel`, `tool-card`, `placeholder-pattern`, `auth-header` y `auth-session-status`, además de `trend-card`. Se eliminan sus copias locales en lnx y en las apps derivadas.
- Las apps consumidoras requieren `^1.0`: las adiciones de componentes llegan con `composer update luinuxscl/lnx-ui` sin tocar `composer.json`.

### Notas de migración
- Sustituye `<x-stat ... />`, `<x-stat-card ... />`, `<x-statistics-panel ... />`, `<x-tool-card ... />`, `<x-placeholder-pattern ... />`, `<x-auth-header ... />` y `<x-auth-session-status ... />` por su equivalente `x-lnx-ui::` y elimina los archivos locales en `resources/views/components/` (solo permanecen en la app los componentes acoplados al host).
- Actualiza `composer.json` a `"luinuxscl/lnx-ui": "^1.0"` y ejecuta `composer update luinuxscl/lnx-ui`.

## [1.6.0] - 2026-10-07

### Añadido
- Paquete Composer privado `luinuxscl/lnx-ui` como fuente única de componentes UI presentacionales para lnx y sus apps derivadas: Blade anónimos bajo el namespace `lnx-ui` (`<x-lnx-ui::... />`) y override por app vía `resources/views/vendor/lnx-ui` (Laravel resuelve la app antes que el paquete).
- Primer componente migrado: `x-lnx-ui::trend-card` (antes `x-trend-card`); se elimina la copia local en lnx y en las apps derivadas.
- Reglas genéricas en `.ai/rules/general.md` y `.ai/rules/views.md`: qué componentes se extraen, cómo se consume el paquete, el alta por app y la precedencia de overrides.

### Notas de migración
- Cada app derivada añade el repositorio y `composer require luinuxscl/lnx-ui`, la línea `@source '../../vendor/luinuxscl/lnx-ui/resources/views';` en `resources/css/app.css`, sustituye `<x-trend-card ... />` por `<x-lnx-ui::trend-card ... />` y elimina `resources/views/components/trend-card.blade.php`.
- El paquete se consume con un repositorio VCS privado (`git@github.com:luinuxscl/lnx-ui.git`, tag `^0.1`); para desarrollo local en vivo se superpone temporalmente un repositorio `type: path` a `../lnx-ui`. CI y despliegue necesitan acceso al repositorio privado (deploy key o token).

## [1.5.0] - 2026-10-07

### Añadido
- Regla local del proyecto `.ai/rules/local.md`, declarada en `local_customizations`: el proyecto derivado escribe aquí sus decisiones permanentes propias y el updater nunca la sobrescribe.
- Fila de `.ai/rules/local.md` en `.ai/rules/index.md`, de modo que cada proyecto derivado recibe el índice ya preparado y no necesita editar un archivo mantenido por el kit.

### Mejorado
- La personalización de la app derivada deja de requerir tocar archivos `kit_maintained`: las reglas propias viven en `local.md` y `scripts/update-agent-kit.py` se aplica sin conflictos.

## [1.4.0] - 2026-10-06

### Añadido
- Herramienta de desarrollo `lnx:reset` (`app/Console/Commands/LnxResetCommand.php`) con soporte `app/Support/DemoUsers.php` y seeder `database/seeders/DemoUserSeeder.php`: limpia cachés, ejecuta `migrate:fresh`, siembra roles y root, y crea cuentas demo (Samantha admin, Leonora usuario y una tanda de usuarios de ejemplo con reparto exacto 90/10 verificados) con contraseña `password`. Bloqueada en producción.
- Pruebas asociadas `tests/Feature/Console/DemoUsersTest.php` y `tests/Feature/Console/ResetCommandTest.php`.

## [1.3.0] - 2026-10-05

### Añadido
- Skill versionada `brand-logos` (`.ai/skills/brand-logos/SKILL.md`): búsqueda en la API pública de SVGL, vendorizado local del SVG y exposición como componente Blade reusable `x-brand-logo`, con reglas de licencia, tema claro/oscuro y accesibilidad.
- Registro de `brand-logos` en el manifiesto del kit, la guía de trabajo con agentes y la lista de skills propias; documentación de la autorización de SVGL en el README.

## [1.2.1] - 2026-10-04

### Mejorado
- La skill `create-ui-component` recomienda extraer piezas visuales repetidas y componer derivados reutilizables cuando corresponda.
- Los fundamentos visuales registran el patrón aprobado de métricas individuales y panel de estadísticas.

## [1.2.0] - 2026-10-04

### Añadido
- Referencia viva de estilos para componentes aprobados: tipografía, títulos, espaciado, superficies e interacción.
- Revisión opcional del procedimiento tras incorporar un componente a la biblioteca, basada en aprendizajes reutilizables.

## [1.1.0] - 2026-10-04

### Añadido
- Skill versionada `create-ui-component` para adaptar capturas a componentes Blade o Livewire reutilizables, con ficha previa, anchos definidos por el contenedor y ejemplos en la galería.
- Dependencia reproducible de `tailwind-design-system` registrada en `skills-lock.json`.

## [1.0.0] - 2026-10-04

### Añadido
- Manifiesto versionado del kit en `.ai/kit-manifest.json`.
- Herramienta determinista de actualización revisable (`scripts/update-agent-kit.py`) con soporte para vista previa (`--preview`), resolución de conflictos, verificación y reversión (`--rollback`).
- Skill versionada `update-agent-kit` (`.ai/skills/update-agent-kit/SKILL.md`).
- Registro del manifiesto del kit en la creación de proyectos nuevos (`scripts/new-project.sh`).
- Validación del manifiesto en `scripts/check-agent-context.py`.
