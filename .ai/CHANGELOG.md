# Registro de cambios del kit para agentes (Agent Kit Changelog)

Todas las modificaciones notables al kit para agentes se documentarán en este archivo.

## [1.6.0] - 2026-10-07

### Añadido
- Paquete Composer privado `luinuxscl/lnx-ui` como fuente única de componentes UI presentacionales para lnx y sus apps derivadas: Blade anónimos bajo el namespace `lnx-ui` (`<x-lnx-ui::... />`) y override por app vía `resources/views/vendor/lnx-ui` (Laravel resuelve la app antes que el paquete).
- Primer componente migrado: `x-lnx-ui::trend-card` (antes `x-trend-card`); se elimina la copia local en lnx y en las apps derivadas.
- Reglas genéricas en `.ai/rules/general.md` y `.ai/rules/views.md`: qué componentes se extraen, cómo se consume el paquete, el alta por app y la precedencia de overrides.

### Notas de migración
- Cada app derivada añade el repositorio y `composer require luinuxscl/lnx-ui`, la línea `@source '../../vendor/luinuxscl/lnx-ui/resources/views';` en `resources/css/app.css`, sustituye `<x-trend-card ... />` por `<x-lnx-ui::trend-card ... />` y elimina `resources/views/components/trend-card.blade.php`.
- En desarrollo local el paquete se enlaza con un repositorio `type: path` (`../lnx-ui`); en despliegue, con un repositorio VCS privado apuntando a un tag.

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
