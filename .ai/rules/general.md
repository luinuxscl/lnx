---
paths:
  - '**'
---

# General

## Estrategia Git Flow para ramas
Este repo usa Git Flow: `main` (producción, estable), `develop` (integración), ramas `feature/nombre` para features nuevos (creadas desde `develop`) y ramas `fix/nombre` o `hotfix/nombre` para correcciones (creadas desde `main`). Antes de trabajar en un nuevo feature o corrección, avisar al usuario y crear la rama correspondiente.

## Documentación siempre actualizada y prioritaria
La documentación es prioritaria y obligatoria. Antes de dar por terminado un feature o corrección, verificar y actualizar la documentación afectada (README.md, guías en .ai/rules, o docs relevantes). Toda funcionalidad nueva o cambio de comportamiento debe reflejarse en la documentación en el mismo PR o commit, nunca después. Cuando se trabaja en áreas no documentadas del repositorio, proponer crear su documentación.

## Cierre de issues de GitHub
Al finalizar un feature o corrección asociado a uno o más issues, revisar sus criterios de aceptación contra la implementación integrada. Cerrar en GitHub cada issue que esté completamente resuelto y dejar abiertos los que tengan trabajo pendiente; no asumir que un merge los cerró automáticamente. Antes de terminar el trabajo, comprobar que los issues resueltos figuren como `CLOSED`.

## Autorización por roles
La autorización se gestiona por roles con `spatie/laravel-permission`, no por permisos granulares. Roles por defecto (`App\Enums\Role`): `root` (superusuario; omite gates y policies vía `Gate::before`) y `admin`. Usar siempre el enum `App\Enums\Role` en lugar de strings literales. Las credenciales root de desarrollo (`App\Support\DevRootCredentials`) nunca deben llegar a producción: el seeder lo bloquea y la UI muestra un banner mientras sigan activas.

## Paquete de UI compartido (`luinuxscl/lnx-ui`)
Los componentes visuales compartidos viven en el paquete Composer `luinuxscl/lnx-ui` (licencia MIT, repositorio público; ver la regla de vistas para el uso y el override). Para incorporarlo a una app: añade en `composer.json` un repositorio VCS (`https://github.com/luinuxscl/lnx-ui.git`) y `composer require luinuxscl/lnx-ui` (el provider se autodescubre); para desarrollo local en vivo, superpón temporalmente un repositorio `type: path` a `../lnx-ui` y restáuralo antes de commitear. Añade `@source '../../vendor/luinuxscl/lnx-ui/resources/views';` en `resources/css/app.css`, porque Tailwind 4 no escanea `vendor/`. CI y despliegue no necesitan credenciales: el repositorio es público. Nunca edites archivos dentro de `vendor/`.
El paquete se desarrolla en el clon local indicado en `.ai/rules/local.md`: edita ahí, verifica con `vendor/bin/pest`, publica (commit en `develop`, tag `vX.Y.Z`, merge a `main`, push) y ejecuta `composer update luinuxscl/lnx-ui` en la app. Por defecto, los componentes visuales reutilizables nuevos se crean en el paquete, no en la app (ver la regla de vistas).

## Vault de Obsidian (contexto de proyectos de Luis)

Los proyectos de Luis tienen ficha y decisiones en el vault `luinuxscl/obsidian-vault` (copia local `~/Obsidian Vault`; en el VPS `/root/Obsidian Vault`). Antes de trabajar en un proyecto que tenga ficha en el vault, consulta `~/Obsidian Vault/AGENTS.md` (política) y `Notes/Proyectos/_indice.md` (mapa de proyectos). Al registrar conocimiento duradero, hazlo en el vault siguiendo su política (pull antes de leer, push al terminar, autoría del agente, sin credenciales). Usa la skill `project-vault-onboard` para crear o refrescar la ficha del proyecto.
