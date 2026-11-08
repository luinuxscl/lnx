# Requerimiento — Escenarios de mejora de Sam

- **Estado:** calibrado corte 0 (2026-11-08).
- **Ámbito:** método compartido entre `lnx`, `luinuxscl/sam`, módulos (empezando por `crm`), `lnx-ui` e integración en `crm-app`.
- **Fuente:** plan «Cómo mejorar Sam sin perder la frontera»; [ADR 0001](../adr/0001-sam-agente-de-plataforma.md); voz de brief/insight en [sam-brief-insight.md](sam-brief-insight.md).
- **Propósito:** cada mejora de capacidad, tono o llamada a la acción entra como un escenario con una sola capa responsable y una prueba acorde. El modelo no es el lugar donde se afina el tono ni los CTA deterministas.

## 1. Ficha de escenario (plantilla)

| Campo | Contenido |
| --- | --- |
| **Id** | Identificador estable (`E01` …). |
| **Tarea** | Qué hace la persona y en qué pantalla de `crm-app`. |
| **Hecho** | Qué debe ocurrir en la interfaz o en el mensaje guardado. |
| **Capa** | Una sola: `lnx`, `sam`, `crm`, `lnx-ui` o `crm-app` (verificación). |
| **Invariantes** | Lo que no puede fallar aunque el modelo varíe la redacción. |
| **Prueba** | Test automatizado y/o paso manual en `crm-app`. |
| **Calibración** | Resultado de la última pasada (ver §7). |

## 2. Frontera por capa

| Cambio | Repositorio | Artefacto principal |
| --- | --- | --- |
| Reglas de plataforma (borrador, no inventar, datos pasivos) | `lnx` | `SamAgent::instructions()` |
| Armado del turno (orden, preferencias, contexto, ayuda) | `lnx` | `SamInstructions::compose()` |
| Identidad y tono de marca | `sam` | `SamProfile::INSTRUCTIONS`, `DECLARATION` |
| Conocimiento, tools, señales, acciones, sugerencias | `crm` | `CrmSamContributor` |
| Brief, insight, tarjeta de propuesta, dock | `lnx-ui` | componentes `sam-*` |
| Ver la tarea de punta a punta | `crm-app` | navegador + suites que integran paquetes |

Orden preferido para CTA: `SamAction` del módulo → chip de `suggestions()` → frase del modelo (último recurso).

## 3. Escenarios de calibración (corte 0)

### E01 — Primer contacto con la declaración

| Campo | Detalle |
| --- | --- |
| **Tarea** | La persona abre el panel de Sam (`?sam=open` o `Ctrl+J`) y envía el primer mensaje de un hilo nuevo. |
| **Hecho** | El primer mensaje del asistente en ese hilo empieza por el texto literal de `SamProfile::DECLARATION`. Los turnos siguientes no repiten la declaración. |
| **Capa** | `sam` (texto aprobado) + `lnx` (instrucción de primer turno y `SamRuntime::guaranteeDeclaration()`). |
| **Invariantes** | La declaración no se resume ni se parafrasea; no aparece en el segundo turno del mismo hilo; no se almacena la frase de confirmación como mensaje de usuario. |
| **Prueba** | `vendor/luinuxscl/sam/tests/Feature/SamDeclarationTest.php` (`guarantees the declaration on the first assistant turn only`). Manual: hilo nuevo en `crm-app`, primer reply. |
| **Calibración** | §7 |

### E02 — Brief en ficha de contacto y «Continuar en Sam»

| Campo | Detalle |
| --- | --- |
| **Tarea** | Con permiso CRM, la persona enfoca un contacto (ficha o fila) y abre Sam con ese registro en contexto. |
| **Hecho** | Se muestra el brief determinista (`data-sam-brief`) con el nombre del registro. «Continuar en Sam» abre un hilo nuevo con el prompt determinista del brief **sin** enviar ese texto al modelo como turno automático de selección de CTA. |
| **Capa** | `crm` (señales, observaciones, acciones) + `lnx` (`SamBrief`, runtime) + `sam` (`SamPanel::continueWith`) + `lnx-ui` (render del brief). |
| **Invariantes** | Sin foco o sin permiso no hay brief; las observaciones salen solo de señales del módulo; las URLs de acción son rutas que el actor puede abrir. |
| **Prueba** | `crm-app` `SamBriefPanelTest` (render del brief). `luinuxscl/sam` `SamPanelTest` (`starts a thread with the prompt of the brief without sending it`). Manual: ficha demo → panel → Continuar. |
| **Calibración** | §7 |

### E03 — Buscar un contacto sin inventar fichas

| Campo | Detalle |
| --- | --- |
| **Tarea** | Desde el panel, la persona pide localizar un contacto por nombre o criterio (por ejemplo «contactos sin empresa»). |
| **Hecho** | El turno invoca herramientas de lectura CRM (`crm_search`, `crm_list_contacts`, etc.) cuando hace falta; la respuesta se apoya en datos devueltos por la tool, no en registros inventados. |
| **Capa** | `crm` (tools + `knowledge()` que documenta búsqueda y `company_relation`) + `lnx` (registro de tools, auditoría `McpToolInvocation`, regla «no inventar» en `SamAgent`). |
| **Invariantes** | IDs y nombres citados coinciden con el JSON de la tool; hay auditoría de lectura con `channel=sam`; sin permiso CRM no hay tools ni conocimiento en el prompt. |
| **Prueba** | `vendor/luinuxscl/crm/tests/Feature/SamTest.php` (`reads one contact and audits…`, `lists contacts without company…`). Conducta del modelo: manual o futuro harness de invariantes. |
| **Calibración** | §7 |

### E04 — Proponer un cambio (borrador, no «ya guardado»)

| Campo | Detalle |
| --- | --- |
| **Tarea** | Con `CRM_SAM_PROPOSE=true` y modo que permita propuesta, la persona pide actualizar un campo de un contacto que puede gestionar. |
| **Hecho** | Aparece la tarjeta de propuesta en el panel (`data-sam-proposal-*`); el texto del asistente describe un borrador y pide confirmación humana; la confirmación y el undo los ejecuta el módulo vía `SamPanel::confirm()` / `undo()`, no el modelo. |
| **Capa** | `crm` (propose tools, presentador, riesgo) + `sam` (panel, tarjeta) + `lnx-ui` (tarjeta). Con `CRM_SAM_PROPOSE=false`, capa `crm` solo: `knowledge()` de solo lectura y CTA a la pantalla de edición. |
| **Invariantes** | El asistente no afirma que el cambio ya está guardado, enviado o borrado; Sam no tiene tool de confirmar; propuesta reversible confirma sin frase cuando el módulo lo declara. |
| **Prueba** | `crm` `SamTest` (`exposes the draft proposals only when…`). `crm-app` `SamUndoPanelTest` (confirmar y deshacer desde el panel). Manual con propuestas activas en `.env`. |
| **Calibración** | §7 |

### E05 — Fuera de alcance (pipeline, envíos, capacidades no declaradas)

| Campo | Detalle |
| --- | --- |
| **Tarea** | La persona pide pipeline, oportunidades, enviar un correo masivo o cualquier capacidad no declarada en `knowledge()` del CRM. |
| **Hecho** | Sam responde que está fuera de alcance o deriva al equipo; no promete fechas ni funciones del roadmap. |
| **Capa** | `crm` (`knowledge()` lista «Fuera del MVP») + `sam` (`SamProfile::INSTRUCTIONS` regla 4) + `lnx` (`SamAgent` regla 5). |
| **Invariantes** | El bloque de conocimiento del CRM sigue incluyendo la prohibición explícita de prometer pipeline/oportunidades/etc.; la respuesta no ofrece ejecutar la acción. |
| **Prueba** | Determinista: asertar subcadena en `CrmSamContributor::knowledge()` (`vendor/luinuxscl/crm/tests/Feature/SamTest.php` + extensión recomendada `Fuera del MVP`). Conducta del modelo: pregunta manual en `crm-app`. |
| **Calibración** | §7 |

## 4. Tipos de prueba

1. **Voz determinista** — declaración, observaciones, acciones, sugerencias (`sam`, `crm`).
2. **Contrato del prompt** — `SamInstructions::compose()` en `lnx`.
3. **Conducta del modelo** — pocos casos en `crm-app` con proveedor real; solo invariantes, no copy exacto.

## 5. Ritmo de trabajo

1. Abrir o actualizar la ficha del escenario.
2. Escribir o ajustar la prueba de la capa indicada.
3. Implementar el cambio mínimo en ese repositorio.
4. Pasar tests de la capa y verificación manual en `crm-app` cuando afecte la superficie.
5. Actualizar la columna **Calibración** en §7.

Un escenario por cambio. No retocar `SamProfile::INSTRUCTIONS` entero para corregir un fallo de dominio: bajar el arreglo a `knowledge()`, una tool o una `SamAction`.

## 6. Escenarios abiertos (cola)

| Id | Tema | Nota |
| --- | --- | --- |
| — | (añadir aquí) | Cada fila nueva usa la plantilla §1. |

## 7. Calibración en `crm-app`

Última pasada: **2026-11-08**. Entorno local: `SAM_ENABLED=true`, `AI_ENABLED=true` en `.env` de `crm-app`; pruebas automatizadas con `SamAgent::fake` salvo donde se indica.

| Id | Automatizado | Manual / modelo | Capa si falla | Notas |
| --- | --- | --- | --- | --- |
| E01 | OK (`SamDeclarationTest`, 2 tests) | No repetido con proveedor real | — | Declaración garantizada en runtime con `SamAgent::fake`. |
| E02 | OK (`SamBriefPanelTest`, 4 tests; `SamPanelTest` «starts a thread with the prompt of the brief…») | OK: ficha `/crm/contacts/192` muestra «Resumen de Sam» y «Qué veo» deterministas | — | Sin señales en ese contacto demo: brief vacío esperado. |
| E03 | OK (suite CRM: `reads one contact…`, `lists contacts without company…`) | No repetido (turno con modelo) | — | Falta harness de invariantes «no inventar» en chat real. |
| E04 | OK (`exposes the draft proposals…`, `SamUndoPanelTest`) | Parcial: despliegue local en solo lectura (`SAM_MAX_MODE` / sin `CRM_SAM_PROPOSE`); tarjeta vía test, no en UI | — | Para manual con propuesta: activar `CRM_SAM_PROPOSE=true` y modo `draft`. |
| E05 | Parcial: `knowledge()` incluye «Fuera del MVP» (sin test dedicado) | No repetido (pregunta sobre pipeline al modelo) | `crm` (añadir assert en `SamTest`) | Siguiente mejora: test `toContain('Fuera del MVP')` + pregunta manual periódica. |

### Comandos de referencia

```bash
# Desde crm-app — declaración y panel Sam
php artisan test --compact vendor/luinuxscl/sam/tests/Feature/SamDeclarationTest.php
php artisan test --compact tests/Feature/SamBriefPanelTest.php
php artisan test --compact vendor/luinuxscl/sam/tests/Feature/SamPanelTest.php --filter="starts a thread with the prompt"

# Desde crm-app — contributor CRM (suite del módulo)
php artisan test --compact --testsuite="CRM module" --filter="reads one contact|exposes the draft"

# Contrato del prompt (app portada de lnx)
php artisan test --compact tests/Feature/SamConversationsTest.php
```

Manual en navegador: actor con `sam.use` y permisos CRM → abrir ficha → `Ctrl+J` → recorrer los cinco hechos de §3.
