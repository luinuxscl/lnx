---
name: consolidar
description: Cierra y consolida el trabajo pendiente de un repositorio actualizando la documentación afectada, agrupando los cambios en commits, integrando ramas según el git flow del proyecto y sincronizando con el remoto. Úsala siempre que el usuario diga "consolidar", "consolida", "deja todo listo", "cierra el trabajo", "cierra el ciclo", "commit y documentación", "mergea y pushea", o pida cerrar/commitear/mergear/pushear los cambios pendientes — incluso si solo escribe una palabra o no menciona explícitamente documentación, commits, merges o push. No la uses para implementar cambios nuevos.
---

# Consolidar

## Propósito

Cuando el usuario pide "consolidar" (o equivalente), ya existe trabajo hecho en el árbol de trabajo y quiere cerrar el ciclo completo: **documentación → commits → integración de ramas → push**. El usuario no debería tener que recordar el procedimiento; tú lo ejecutas y solo pides una confirmación simple antes de tocar el repositorio.

## Principios

1. **La documentación es obligatoria y va en el mismo commit que el cambio.** No dejes docs para después.
2. **Git flow:** `main` = producción estable; `develop` = integración; `feature/*` desde `develop`; `fix/*` y `hotfix/*` desde `main`. Antes de commitear una corrección o feature, propón la rama correspondiente y confírmala con el usuario.
3. **Nunca** commitees secretos (`.env`, llaves), ni uses `--force`/force-push, ni saltes hooks.
4. Agrupa en commits lógicos y atómicos, no en un commit gigante.

Antes de planificar, lee las reglas del repositorio: `.ai/rules/index.md` y los archivos cuyos globs apliquen a los cambios. Si `.ai/rules` no existe, usa el README y el git flow por defecto. El repositorio puede tener convenciones propias de commits y de ramas que mandan sobre las de arriba.

## Flujo

### 1. Reconocer el estado (sin modificar nada)

```bash
git status --short
git diff --stat
git log --oneline -10
git branch -vv
git remote -v
```

Determina: archivos modificados/nuevos y qué representan, rama actual y su relación con el upstream, y commits locales sin pushear.

### 2. Detectar la documentación afectada

Revisa los cambios y decide qué documentación debe actualizarse en el mismo commit:

- `README.md` — comportamiento, stack, estructura, comandos.
- `.ai/rules/*.md` — reglas permanentes, si el cambio fija una convención o decisión.
- Documentación específica del área, si existe.

Si un cambio de comportamiento no está documentado en ningún lado, propón dónde documentarlo.

### 3. Armar el plan

Presenta un plan breve y concreto, sin relleno:

```
Cambios detectados: <n> archivos en <rama>
Documentación:  <archivos a actualizar, o "sin cambios">
Commits:        <n> commit(s) propuestos, con su mensaje>
Ramas:          <rama a crear/mergear, si aplica>
Merge:          <develop -> main, o "no">
Push:           <ramas a pushear, o "no">
```

### 4. Confirmar UNA sola vez, de forma sencilla

Usa la herramienta `question` con opciones claras; no preguntes campo por campo. Ejemplo:

- **"Sí, procede con todo"** — documentación + commits + merge + push. *(Recommended)*
- **"Solo docs y commits"** — sin merge ni push.
- **"Solo commits"** — sin documentación ni merge/push.

Si el plan requiere crear una rama (feature/fix/hotfix), inclúyelo en el plan y en la confirmación; no crees ramas sin avisar.

Si el usuario ya fue explícito en su mensaje sobre qué hacer (por ejemplo, "consolida y pushea a main"), no vuelvas a preguntar lo mismo: confirma solo si hay ambigüedad o riesgo.

### 5. Ejecutar

- **Documentación:** edítala antes de commitear y agrega esos archivos al commit correspondiente.
- **Commits:** mensajes en el idioma del repositorio (este proyecto: español) con prefijo `tipo: descripción` (`fix:`, `feat:`, `docs:`, `chore:`, `refactor:`). Un commit por unidad lógica; la documentación va con el cambio que describe.
- **Ramas y merge:** sigue el git flow del repositorio. Para integrar usa `git merge <rama> --no-ff -m "merge: ..."` salvo que el repo indique otra cosa.
- **Push:** `git push origin <rama>` para cada rama. Nunca force-push.

Ejecuta solo lo confirmado. Si algo falla (tests, hooks, push rechazado), detente, reporta el error exacto y no lo fuerces.

### 6. Reportar

Cierra con un resumen mínimo: rama(s), hash(es) de commit, documentación actualizada, merge/push realizados y estado final (`git status`, upstream al día).

## Casos borde

- **Sin cambios y sin commits locales pendientes:** dilo y no hagas nada más.
- **Solo commits locales sin pushear:** el plan es merge (si aplica) + push; no hay docs que tocar.
- **Cambios sin relación entre sí:** sepáralos en commits distintos y dilo en el plan.
- **No hay documentación afectada:** indícalo explícitamente; no inventes documentación.
- **El repositorio no usa git flow:** adapta el plan a sus reglas reales y confírmalo.
