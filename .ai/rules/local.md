---
paths:
  - '**'
---

# Reglas locales del proyecto

Este archivo pertenece al proyecto derivado, no al kit lnx: figura como personalización local en `.ai/kit-manifest.json` y `scripts/update-agent-kit.py` nunca lo sobrescribe. Úsalo para las decisiones permanentes propias de este proyecto que no deban vivir en las reglas genéricas del kit.

- No edites archivos mantenidos por el kit (`.ai/kit-manifest.json`, `kit_maintained`) para dejar reglas propias: si el cambio es reutilizable por otras apps, va en lnx.
- Registra aquí cada regla permanente nueva solo a petición explícita del usuario (por ejemplo con `record-rule`).
- Mantén el archivo indexado en `.ai/rules/index.md`; el kit ya trae su fila, no la reescribas.

## Reglas de este proyecto

El kit deja esta sección vacía. El proyecto derivado describe aquí sus decisiones de marca, dominio, roles, navegación, contenido y despliegue.

## Desarrollo del paquete `lnx-ui`
La carpeta de desarrollo en vivo del paquete compartido de componentes es `/home/luinux/Code/lnx-ui`. Para cambiar un componente: edita ahí, verifica con `vendor/bin/pest`, publica (commit en `develop`, tag `vX.Y.Z`, merge a `main`, push) y ejecuta `composer update luinuxscl/lnx-ui` en esta app. Para iterar sin publicar, superpón temporalmente un repositorio `path` a esa carpeta y restáuralo antes de commitear. Por defecto, los componentes visuales reutilizables nuevos se crean en el paquete, no en `resources/views/components/`.
