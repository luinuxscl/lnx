---
name: lnx-ui-release
description: "Publica el paquete luinuxscl/lnx-ui desde su checkout de desarrollo: verifica, commitea en develop, mergea a main, crea y sube el tag SemVer, y actualiza opcionalmente los proyectos que lo requieren. Úsala siempre que haya que publicar o versionar cambios del paquete lnx-ui (componentes, galería, recursos o skills), cuando se pida «subir un cambio al paquete», «tag de nueva versión», «publicar lnx-ui» o «dejar lnx-ui listo para actualizar en las apps», y cuando haya que propagar esa versión a las instancias de lnx que lo tienen como requerimiento."
---

# Publicar el paquete lnx-ui (lnx-ui-release)

> Fuente canónica: el paquete `lnx-ui`, en su skill de Boost `lnx-ui-release`. Mantén esta copia en sincronía con ella.

Este procedimiento deja el paquete `luinuxscl/lnx-ui` publicado y consumible: parte del checkout de desarrollo, verifica, integra `develop` y `main`, crea el tag SemVer y sube todo a `origin`. Después, opcionalmente, actualiza los proyectos que requieren el paquete. El paquete se consume como VCS privado (`^1.0`), así que un cambio no llega a las apps hasta que existe un tag nuevo y cada app ejecuta `composer update luinuxscl/lnx-ui`.

## Cuándo usar

- Cuando hay cambios pendientes en el paquete `lnx-ui` (componentes Blade, `design-gallery`, traducciones, skills de Boost del propio paquete, etc.) y se quieren publicar.
- Para decidir y aplicar el salto SemVer (`patch`/`minor`/`major`) y dejar el tag correspondiente.
- Para propagar la versión nueva a las instancias de lnx que tienen el paquete como requerimiento.

## Cuándo NO usar

- Para cambiar un componente dentro de una app consumidora: eso se hace superponiendo el componente en la carpeta de sobrescritura de vistas del proveedor o editando el paquete; no es una publicación.
- Para actualizar dependencias ajenas al paquete (`composer.json`/`package.json` generales): usar Composer/npm sin este flujo.
- Para el kit de agentes del proyecto (`.ai`): usar `update-agent-kit`. Esta skill es del paquete de UI.

## Fuentes de verdad y checkout de desarrollo

- **Edita siempre en el checkout de desarrollo del paquete**, por defecto `/home/luinux/Code/lnx-ui`. Compruébalo antes de tocar nada; si el árbol de trabajo es `vendor/luinuxscl/lnx-ui` dentro de una app, **detente**: ese directorio es una copia instalada, no la fuente. Cualquier cambio ahí se pierde en el siguiente `composer update`.
- La ruta del checkout puede figurar en `.ai/rules/local.md` del proyecto; úsala si difiere del valor por defecto.
- El repositorio es `git@github.com:luinuxscl/lnx-ui.git`; el flujo usa las ramas `develop` y `main` (Git-Flow sin PR), y los tags anotados `vX.Y.Z`.

## Guardarraíles

- Nunca hagas force-push ni reescribas historia.
- Confirma el salto SemVer con el usuario antes de etiquetar.
- No actualices proyectos consumidores sin preguntar primero.
- No incluyas en el commit cambios de otras ramas ni archivos no relacionados; prepara rutas específicas.
- Deja `develop` activo y el árbol limpio al terminar.

## Procedimiento

### Paso 0: Comprobar estado

```sh
cd /home/luinux/Code/lnx-ui
git status --short --branch
git fetch origin --tags
git checkout develop
git pull --ff-only origin develop
git log --oneline origin/main..develop   # commits que entrarán en main
git log --oneline -5
```

Debe haber un árbol limpio o solo los cambios que se van a publicar. Si `develop` trae commits que no forman parte de esta publicación, avisa al usuario antes de mergear a `main`.

### Paso 1: Verificar

```sh
composer install
vendor/bin/pest
vendor/bin/pint
git diff
```

Revisa el diff real que se va a publicar. Corrige y repite hasta que pase todo. Si hay tests en la app que consume el cambio, verifícalos también.

### Paso 2: Decidir el salto SemVer

Pregunta al usuario qué tipo de versión corresponde y justifícala:

- `patch` (`x.y.Z`): correcciones sin cambios de API.
- `minor` (`x.Y.0`): adiciones compatibles (componente nuevo, prop opcional, skill nueva, recurso nuevo).
- `major` (`X.0.0`): cambio incompatible (prop renombrada/eliminada, cambio de contrato).

Calcula la versión a partir del último tag:

```sh
git describe --tags --abbrev=0
git tag --sort=-v:refname | head -5
```

### Paso 3: Commit en `develop`

Prepara rutas concretas y commitea con conventional commits, en español y sin acentos, como el historial del paquete (p. ej. `feat(<ámbito>): <descripción>`):

```sh
git add <rutas>
git commit -m "feat(<ámbito>): <descripción>"
git push origin develop
```

### Paso 4: Integrar en `main`

```sh
git checkout main
git merge --ff-only origin main      # antes de mergear, solo si no hay trabajo local pendiente
git merge --no-ff develop -m "Merge branch 'develop' into main"
git push origin main
git checkout develop
```

Si `main` local no existe, créala siguiendo `origin/main`.

### Paso 5: Crear y subir el tag

El tag va sobre el **commit de `develop`** que se acaba de publicar, no sobre el commit de merge de `main`. Es anotado. Estando en `develop`, confirma el commit antes de etiquetar y verifica que el tag apunta ahí:

```sh
git branch --show-current                     # debe ser develop
git rev-parse develop                         # commit que se va a etiquetar
git tag -a vX.Y.Z -m "vX.Y.Z"
git push origin vX.Y.Z
git ls-remote --tags origin | grep "vX.Y.Z"   # verifica que el tag está publicado
git rev-parse "vX.Y.Z^{commit}"               # debe coincidir con el commit de develop
```

Algunas versiones anteriores (`v1.4.0`, `v1.4.1`, `v1.5.0`) quedaron sobre el commit de merge de `main`; `v1.3.0` y `v1.6.0` siguen esta convención. Un tag publicado no se mueve ni se reescribe: si una versión quedó mal ubicada, se corrige con una versión nueva.

### Paso 6: Dejar el repo limpio

```sh
git checkout develop
git status --short --branch
git log --oneline -3
```

### Paso 7: Actualizar proyectos consumidores (opcional, siempre preguntando)

Pregunta al usuario la lista de proyectos que requieren el paquete (`luinuxscl/lnx-ui`). No la asumas ni escanees sin pedirlo. Si no quiere actualizar ninguno, termina aquí.

También puedes recordarle que en cada app `root` la página `/admin/packages/lnx-ui` muestra la versión instalada y la última disponible (requiere `GITHUB_TOKEN`).

Por cada proyecto indicado:

```sh
cd <ruta-del-proyecto>
git status --short --branch          # confirma que no hay cambios que se puedan perder
composer update luinuxscl/lnx-ui
```

Después, en cada app, ejecuta sus comprobaciones propias (tests, `npm run build`, etc.) y registra el resultado. Si un proyecto no debe tocarse todavía, respétalo.

## Reporte

Al terminar, informa de forma concisa:

- Versión publicada y tipo de salto (`vX.Y.Z`, minor/patch/major).
- Ramas actualizadas y pusheadas (`develop`, `main`) y tag empujado.
- Commit(s) incluidos.
- Proyectos consumidores actualizados, con su resultado de verificación, y cuáles quedaron pendientes.

## Ejemplo de invocación

Petición: «acabo de arreglar el espaciado de `statistics-panel`, publícalo y actualiza las apps».

Resultado esperado: commit en `develop` (`fix(statistics-panel): …`), merge a `main`, tag `vX.Y.Z` (patch) empujado, y —tras preguntar— `composer update luinuxscl/lnx-ui` en cada app indicada con sus checks en verde.
