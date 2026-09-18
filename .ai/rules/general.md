---
paths:
  - '**'
---

# General

## Estrategia Git Flow para ramas
Este repo usa Git Flow: `main` (producción, estable), `develop` (integración), ramas `feature/nombre` para features nuevos (creadas desde `develop`) y ramas `fix/nombre` o `hotfix/nombre` para correcciones (creadas desde `main`). Antes de trabajar en un nuevo feature o corrección, avisar al usuario y crear la rama correspondiente.

## Documentación siempre actualizada y prioritaria
La documentación es prioritaria y obligatoria. Antes de dar por terminado un feature o corrección, verificar y actualizar la documentación afectada (README.md, guías en .ai/rules, o docs relevantes). Toda funcionalidad nueva o cambio de comportamiento debe reflejarse en la documentación en el mismo PR o commit, nunca después. Cuando se trabaja en áreas no documentadas del repositorio, proponer crear su documentación.
