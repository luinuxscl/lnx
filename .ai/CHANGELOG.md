# Registro de cambios del kit para agentes (Agent Kit Changelog)

Todas las modificaciones notables al kit para agentes se documentarán en este archivo.

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
