---
name: update-agent-kit
description: Guía determinista para comparar, previsualizar, actualizar y revertir la base agéntica del kit lnx en proyectos derivados.
---

# Actualizar el kit para agentes (update-agent-kit)

Este procedimiento establece el flujo para comprobar la compatibilidad, previsualizar diferencias, aplicar actualizaciones y revertir cambios en los activos del kit de agentes (`.ai/rules/`, `.ai/skills/`, `.ai/guidelines/`, manifiesto `.ai/kit-manifest.json` y scripts de soporte) en un proyecto derivado sin sobreescribir silenciosamente personalizaciones locales ni alterar dependencias de la aplicación.

## Cuándo usar
- Cuando se desea actualizar los componentes del kit agéntico desde la versión de referencia de `lnx` hacia un proyecto derivado.
- Al verificar la compatibilidad de una nueva versión del kit de agentes antes de aplicarla.
- Para inspeccionar diferencias y resolver conflictos entre reglas/skills del kit y modificaciones locales.
- Para revertir una actualización del kit que haya causado inconsistencias.

## Cuándo NO usar
- Para actualizar dependencias de PHP (`composer.json`) o JS (`package.json`) de la aplicación (usar Composer/npm directamente).
- Para sincronizar código de negocio o modelos de la aplicación derivada.

## Requisitos y entradas
1. **Manifiesto del kit**: Archivo `.ai/kit-manifest.json` presente en la raíz del proyecto.
2. **Entorno local listo**: Python 3.9+ disponible para ejecutar la herramienta de actualización.
3. **Estado git limpio o recuperable**: Árbol de trabajo sin cambios no guardados antes de aplicar actualizaciones.

## Resultado esperado
- Verificación exitosa de compatibilidad de stack (PHP, Laravel, Livewire, Tailwind) y agentes.
- Previsualización clara (`--preview`) de archivos a añadir, modificar o conservar sin escribir cambios silenciosos.
- Preservación determinista de personalizaciones locales (`local_customizations` y `extensions`).
- Actualización explícita de archivos del kit (`kit_maintained`) y actualización del manifiesto `.ai/kit-manifest.json`.
- Verificación posterior limpia mediante `python3 scripts/check-agent-context.py`.
- Capacidad de reversión probada (`--rollback`) en caso de necesitar restaurar el estado previo.

---

## Procedimiento paso a paso

### Paso 1: Comprobar compatibilidad de la actualización
Ejecutar la comprobación de compatibilidad con la versión/fuente del kit:

```bash
python3 scripts/update-agent-kit.py --check-compat
```

Verificar que la versión de PHP, Laravel y los agentes configurados sean compatibles antes de proceder.

### Paso 2: Previsualizar diferencias y conflictos (Dry Run)
Antes de modificar cualquier archivo, ejecutar la previsualización de diferencias:

```bash
python3 scripts/update-agent-kit.py --preview
```

El script mostrará la lista de archivos mantenidos por el kit (`kit_maintained`), indicando:
- Archivos sin cambios (`OK`).
- Archivos que se actualizarán limpiamente (`UPDATE`).
- Archivos con modificaciones locales que requieren resolución de conflictos (`CONFLICT`).

### Paso 3: Aplicar la actualización de forma explícita
Una vez revisadas las diferencias, aplicar la actualización especificando la estrategia de resolución de conflictos deseada:

- **Conservar personalizaciones locales por defecto**:
  ```bash
  python3 scripts/update-agent-kit.py --apply --strategy keep-local
  ```
- **Aceptar la versión del kit para archivos conflictivos**:
  ```bash
  python3 scripts/update-agent-kit.py --apply --strategy accept-kit
  ```

El script creará un snapshot de seguridad/backup previo a la modificación.

### Paso 4: Validar la coherencia del contexto
Tras aplicar la actualización, verificar la integridad de las reglas y habilidades:

```bash
python3 scripts/check-agent-context.py
```

Si existen enlaces rotos o referencias desalineadas, corregir los archivos antes de confirmar los cambios en Git.

### Paso 5: Revertir si es necesario (Rollback)
Si la actualización presenta inconsistencias imprevistas, revertir al estado previo:

```bash
python3 scripts/update-agent-kit.py --rollback
```

Comprobar nuevamente con `python3 scripts/check-agent-context.py` para asegurar que el proyecto regresó al estado estable.
