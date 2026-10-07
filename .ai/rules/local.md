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
