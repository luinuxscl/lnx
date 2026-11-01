# Registro de cambios de lnx

Todas las releases notables del kit **lnx** se documentan en este archivo. El detalle de los cambios del kit para agentes (`.ai/`) vive en [`.ai/CHANGELOG.md`](.ai/CHANGELOG.md).

El kit sigue [SemVer](https://semver.org/lang/es/) y cada release se publica con una etiqueta anotada `vX.Y.Z` sobre `main`. La versión vigente se declara en `.ai/kit-manifest.json` como `lnx_version` y se copia a cada proyecto creado con `scripts/new-project.sh`.

## [1.0.0] - 2026-11-01

Primera release bajo el esquema SemVer del kit completo.

### Añadido

- **Esquema de versionado `vX.Y.Z`:** `lnx_version` en `.ai/kit-manifest.json`, etiquetas anotadas sobre `main` y reglas MAJOR/MINOR/PATCH documentadas en el README.
- **Comprobación de versión en proyectos derivados:** `python3 scripts/update-agent-kit.py --source-kit <lnx> --check-version` compara el `lnx_version` del proyecto con el del clon y devuelve código 1 si está desactualizado o sin versión registrada.

### Cambiado

- **Vista inicial (landing):** pasa a una composición de una sola pantalla, sin header, selector de idioma ni control de tema; incluye halo del color de acento, franja compacta del stack y footer del kit.
- **Apariencia por defecto:** la primera visita arranca en modo claro (`Appearance::defaultColorScheme()` y semilla en `partials/head.blade.php`), sin romper la opción **System** de los ajustes de cuenta.
- **Sin `class="dark"` fijo** en `welcome` ni en los layouts: el modo guardado se aplica desde el `<head>` antes de pintar.
