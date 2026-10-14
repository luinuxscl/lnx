---
name: analyze-roadmap
description: Analiza el roadmap del proyecto (ROADMAP.md) frente al tracker real (GitHub Issues) y al estado de Git para detectar desalineaciones y proponer el próximo trabajo. Úsala siempre que se pida analizar o revisar el roadmap, comprobar si está al día, detectar desalineaciones entre etapas e issues, conciliar el orden escrito con el de las issues, proponer prioridades o el siguiente trabajo, o actualizar el orden del roadmap, incluso si no se nombra la skill ni el archivo.
---

# Analizar el roadmap (analyze-roadmap)

Este procedimiento contrasta el documento de prioridades del proyecto (`ROADMAP.md` en lnx y sus derivados) contra las fuentes reales —el tracker de issues y el estado de Git— para responder tres preguntas: **¿está al día?**, **¿dónde no cuadra?** y **¿cuál es el próximo trabajo accionable?**. GitHub es la fuente del estado de cada issue; el roadmap es un resumen que puede quedar desalineado y hay que verificarlo, no darlo por cierto.

## Cuándo usar

- Cuando se pide «analiza el roadmap», «revisa el roadmap», «¿está al día?», «¿qué hay pendiente?» o «¿qué sigue?».
- Para detectar desalineaciones entre etapas del roadmap y el estado real de las issues (abiertas/cerradas), o entre el orden global y los órdenes parciales escritos en cada issue.
- Para proponer o reordenar prioridades a partir del estado real.
- Antes de decidir el siguiente trabajo o de abrir una etapa nueva.

## Cuándo NO usar

- Para **implementar** una issue (usar `implement-feature`).
- Para el **cierre de sesión completo** con commit/merge/push (usar `git-closeout`; esta skill solo analiza y, como mucho, propone el cambio de documento).
- Para **cerrar o reabrir** issues por sí solo: este procedimiento informa; cerrar es una acción aparte que el usuario decide.
- Para debatir prioridades de producto sin datos: el orden final lo decide el mantenedor.

## Requisitos y entradas

1. **Documento de prioridades**: por defecto [`ROADMAP.md`](file:///home/luinux/Code/lnx/ROADMAP.md). Si el proyecto usa otro, localízalo por el `README.md` (sección de trabajo con agentes) o `AGENTS.md`.
2. **Acceso al tracker**: `gh` autenticado y con permiso de lectura sobre el repo (`gh repo view --json nameWithOwner`). Si no hay tracker accesible, indícalo y marca el análisis como «no verificado».
3. **Estado de Git**: ramas, tags y qué está integrado en la rama principal (para comprobar las etapas que el roadmap declara «integradas» o «cerradas»).

## Procedimiento

### 1. Reunir las fuentes (solo lectura)

- Lee el roadmap completo y anota: fecha de revisión, etapas (abiertas/cerradas), «próximo trabajo», tabla de orden, decisiones de secuencia y estado actual.
- Trae el tracker real, no lo asumas:

  ```sh
  gh repo view --json nameWithOwner -q .nameWithOwner
  gh issue list --state all --limit 200 --json number,title,state,labels,createdAt,closedAt
  ```

- Trae el estado de Git útil para contrastar:

  ```sh
  git fetch --all --prune
  git branch -vv --all
  git log --oneline --decorate -20
  git tag --sort=-creatordate | head
  ```

### 2. Construir el mapa etapa ↔ issue

Para cada etapa del roadmap, lista sus issues y el **estado real** del tracker. Aparte, lista las issues **abiertas sin etapa**. Los enlaces del roadmap son a `https://github.com/<owner>/<repo>/issues/<n>` (o a otro repo, p. ej. `luinuxscl/lnx-ui`): resuélvelos contra el tracker correspondiente y marca los que no existan.

### 3. Detectar desalineaciones

Recorre esta lista y registra cada hallazgo con **evidencia** (número de issue, línea del roadmap, ref de Git) y **severidad** (alta / media / baja):

- **Estado invertido**: etapa marcada «próximo trabajo» que ya está cerrada; etapa «cerrada» con issues abiertas.
- **Issue mal referenciada**: issue abierta citada como cerrada, o cerrada citada como abierta.
- **Huérfanas**: issues abiertas que no aparecen en ninguna etapa.
- **Próximo trabajo inválido**: apunta a una issue cerrada o a una bloqueada por dependencias aún abiertas.
- **Dependencias**: una issue depende de otra que no existe o sigue abierta.
- **Orden en conflicto**: el orden global difiere del orden parcial escrito dentro de una issue.
- **Links rotos**: número de issue inexistente en su repositorio.
- **Deriva de fecha**: la «última revisión» es anterior a cambios reales en las etapas (issues cerradas/abiertas después).
- **Git incoherente**: etapas declaradas «integradas/cerradas» cuyos commits no están en `main`; ramas sin merge; ausencia de tags si el proyecto versiona por tags.

### 4. Informe (formato fijo)

El informe es **corto y orientado a lo que viene**: abre con el **siguiente trabajo**, sigue con el **listado de issues abiertas** y cierra con un **estado de etapas que colapsa las cerradas** en una sola fila. Si no hay desalineaciones, dilo en una línea; el detalle aparece solo cuando existe. Prioriza leer rápido: nada de relleno ni de repetir los criterios de cada issue.

```
# Roadmap — <fecha> · <al día | N desalineaciones>

## Siguiente
**#NN Título** — <por qué ahora; con qué otras issues va; bloqueos si los hay>. <link>
<Si está bloqueada, añade la siguiente accionable: **#MM Título** (link).>

## Issues abiertas
| # | Issue | Etapa | Bloqueo |
| --- | --- | --- | --- |
| #NN | <título> | <etapa> | — / <dependencia> |

## Etapas
| Etapa | Estado | Issues |
| --- | --- | --- |
| 1–N | cerradas ✓ | <rango o lista compacta> |
| N+1 | abierta | <secuencia compacta, p. ej. #52 → #53 → #48> |

## Desalineaciones
<«Sin desalineaciones.» o la tabla solo con las encontradas:>

| Severidad | Hallazgo | Evidencia | Corrección sugerida |
| --- | --- | --- | --- |
| alta/media/baja | ... | issue #n / ROADMAP.md:línea / ref | ... |
```

Reglas del informe: las **etapas cerradas van agrupadas** (una fila, no una por etapa); la etapa abierta se detalla con su secuencia; el listado de issues abiertas es lo único que se enumera una a una. Mantén el informe por debajo de ~30 líneas salvo que haya muchas desalineaciones.

### 5. Actualizar el documento (solo si el usuario lo pide)

Si se pide aplicar cambios, hazlo **contra la fuente canónica** y siguiendo las reglas de mantenimiento del propio roadmap:

- Actualiza la fecha de revisión.
- No inventes entradas: solo refleja lo que cambió (estado de issue, dependencia, bloqueo, prioridad, siguiente trabajo).
- Conciliación, no duplicación: los criterios de aceptación viven en las issues; el roadmap los resume.
- Si cambia el orden, deja una breve razón en las decisiones de secuencia.
- Después de editar, ofrece commit/push como paso separado; **no commitees sin que el usuario lo pida**.

## Reglas de salida

- El estado de cada issue se lee del tracker en el momento del análisis; **no** se deduce del texto del roadmap.
- Cada hallazgo lleva evidencia verificable y una corrección concreta.
- Si no se pudo consultar el tracker, dilo explícitamente y marca el análisis como no verificado.
- No cierres ni reabras issues desde aquí, ni inicies la implementación de la próxima tarea.
