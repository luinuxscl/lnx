# Registro de cambios del kit para agentes (Agent Kit Changelog)

Todas las modificaciones notables al kit para agentes se documentarán en este archivo.

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
