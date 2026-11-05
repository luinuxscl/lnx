# Requerimiento — Brief e insight de Sam (corte 1b)

- **Estado:** aprobado (2026-11-05).
- **Ámbito:** contrato en `lnx`, copy de dominio en `crm`, render en `lnx-ui`, integración en `crm-app`.
- **Fuente:** roadmap `Notes/Proyectos/like-innovacion/roadmap-sam.md` §1b; issue `crm-app#9`.
- **Decisiones aprobadas:** título visible «Resumen de Sam»; «Profundizar» abre la ficha; «Continuar en Sam» inicia una conversación nueva; narrativa cacheada por sesión en 1b.

## 1. Propósito

Convertir las señales deterministas que el módulo ya calcula en dos piezas de comprensión:

- **Brief:** resumen del registro en foco al abrir Sam. Híbrido: narrativa del modelo + lista determinista de señales. Fallback determinista si el modelo no está disponible.
- **Insight:** contexto accionable de una fila de la lista. Determinista. Responde «qué veo» y «qué puedes hacer», con «continuar en Sam» y «profundizar».

El brief y el insight no escriben, no confirman y no inventan observaciones. Solo leen señales ya calculadas por el módulo y enlazan a pantallas existentes.

## 2. Definiciones

- **Señal (`SamSignal`):** observación determinista del módulo. `name` estable, `value` corto y `evidence` (enlace a la pantalla donde se puede verificar). No la produce el modelo.
- **Observación:** frase en el idioma del usuario que traduce una señal. La compone el presentador del módulo, no el modelo.
- **Acción (`SamAction`):** enlace a una pantalla existente donde la persona puede actuar. Tiene `key`, `label`, `description` y `url`. No ejecuta escrituras.
- **Brief:** tarjeta de resumen del registro en foco. No consume un turno de chat.
- **Insight:** popover/hoja de contexto de una fila. Se calcula al abrir, no al renderizar la lista.

## 3. Composición del brief

### 3.1 Ubicación y comportamiento

- Se muestra en el panel de Sam cuando hay un registro en foco (contacto o empresa) y el panel se abre.
- Se renderiza como tarjeta sobre el hilo, no como mensaje. No entra en el historial de la conversación.
- Render determinista inmediato. La narrativa del modelo se solicita después y se sustituye cuando llega; si falla o tarda más de 4 s, se queda el fallback.
- Si hay una conversación activa sobre el mismo registro, la tarjeta aparece colapsada por defecto y se puede expandir. Si el registro cambia, la tarjeta se recalcula.
- El brief no bloquea el chat: el campo de escritura está disponible desde el primer momento.

### 3.2 Estructura

```
[Resumen de Sam]                                  [colapsar]
[Narrativa: 2-4 frases, máx. 60 palabras]         (modelo o fallback)
[Qué veo]
  - Observación 1  (enlace de evidencia)
  - Observación 2
  - ...
[Qué puedes hacer]
  - Acción 1 — descripción  (enlace)
  - Acción 2 — descripción
  - Acción 3 — descripción
  [Ver más]  (si hay más de 3)
[Continuar en Sam]
```

- **Título:** «Resumen de Sam» (es) / «Sam summary» (en). No usar «brief» en la interfaz.
- **Narrativa:** párrafo único. Si no hay modelo, frase de fallback (ver §7).
- **Qué veo:** lista completa de observaciones, ordenada por severidad (§6). Cada observación enlaza a su evidencia. Sin límite de líneas en el brief; si son muchas, se agrupan por severidad.
- **Qué puedes hacer:** hasta 3 acciones explicadas. Si hay más, «Ver más» expande la lista en la misma tarjeta.
- **CTA:** «Continuar en Sam» inicia una conversación nueva con el contexto del registro y un mensaje precargado (§8).

## 4. Composición del insight

### 4.1 Ubicación y comportamiento

- Se abre desde el botón de fila (icono de destellos) en las listas de contactos y empresas, y en los resultados de búsqueda.
- Popover anclado a la fila en escritorio; hoja inferior en móvil. No modal; `Esc` cierra y devuelve el foco al botón.
- Se calcula en el servidor al abrir (no en el render de la lista). Latencia objetivo: p95 ≤ 300 ms.
- No abre el panel de Sam por sí solo. «Continuar en Sam» lo abre.

### 4.2 Estructura

```
[{label} · Contacto|Empresa]                      [cerrar]
[Qué veo]
  - Observación 1
  - Observación 2
  - Observación 3
  - y N más  (si hay más de 3)
[Qué puedes hacer]
  - Acción 1 — descripción
  - Acción 2 — descripción
  - Acción 3 — descripción
  [Ver más]  (si hay más de 3)
[Continuar en Sam]  [Profundizar]
```

- **Encabezado:** nombre del registro y tipo («Contacto» / «Empresa»). Sin título «Insight».
- **Qué veo:** máximo 3 observaciones, ordenadas por severidad. Si hay más, una línea «y N más» (no enlazada) y el resto queda en el brief («Profundizar»).
- **Qué puedes hacer:** máximo 3 acciones explicadas. «Ver más» expande la lista en el mismo popover/hoja.
- **CTAs:** «Continuar en Sam» (primario) y «Profundizar» (secundario).

## 5. Redacción

### 5.1 Reglas generales

- Idioma: locale del usuario. Español por defecto; inglés espejo.
- Persona: segunda persona, presente, neutral y directa. Sin «¡» ni emojis. Sin lenguaje comercial.
- Longitud: observaciones ≤ 20 palabras; descripciones de acción ≤ 15 palabras; narrativa ≤ 60 palabras.
- Números en dígitos. Singular/plural correcto (1 contacto / 2 contactos).
- No usar nombres técnicos de señales (`duplicate_email`, etc.) en la interfaz.
- No afirmar causas ni consecuencias no observadas. No prometer acciones ni decir que Sam hizo algo.
- No repetir el nombre del registro en cada observación; el encabezado ya lo muestra.
- Cada observación debe poder verificarse con su enlace de evidencia.
- Las observaciones se guardan como frases completas. Al incrustarlas en otra frase (por ejemplo, el fallback tras dos puntos), el presentador ajusta la mayúscula inicial y el punto final.

### 5.2 Tabla de señales → observaciones (es)

| Señal | Valor | Observación | Acción(es) |
|---|---|---|---|
| `missing_company` | true | Sin empresa asignada. | Asignar empresa |
| `missing_owner` | true | Sin propietario asignado. | Asignar propietario |
| `missing_channel` | true | Sin canal de contacto: no hay correo, teléfono ni móvil. | Añadir canal |
| `duplicate_email` | N | Hay :count contacto más con este correo. / Hay :count contactos más con este correo. | Comparar duplicados |
| `duplicate_name` | N | Hay :count contacto más con este nombre. / Hay :count contactos más con este nombre. | Revisar homónimos |
| `archived_company` | true | La empresa está archivada. | Abrir empresa |
| `missing_website` | true | Sin sitio web. | Añadir sitio web |
| `duplicate_website` | N | Hay :count empresa más con este dominio. / Hay :count empresas más con este dominio. | Comparar empresas |
| `archived_with_active_contacts` | N | La empresa está archivada y tiene :count contacto activo. / La empresa está archivada y tiene :count contactos activos. | Ver contactos activos / Abrir empresa |

### 5.3 Tabla de acciones → texto (es)

| Acción | Etiqueta | Descripción | Destino |
|---|---|---|---|
| `assign_company` | Asignar empresa | Agrupa el contacto por cuenta. | Editar contacto |
| `assign_owner` | Asignar propietario | Deja claro quién lo gestiona. | Editar contacto |
| `add_channel` | Añadir canal | Sin canal no puedes contactar. | Editar contacto |
| `compare_email_duplicates` | Comparar duplicados | Evita escribir al contacto equivocado. | Contactos filtrados por correo |
| `review_namesakes` | Revisar homónimos | Aclara si son la misma persona. | Contactos filtrados por nombre |
| `view_company` | Abrir empresa | Revisa el estado de la cuenta. | Ficha de la empresa |
| `add_website` | Añadir sitio web | Completa el perfil de la empresa. | Editar empresa |
| `compare_website_duplicates` | Comparar empresas | Evita cuentas duplicadas. | Empresas filtradas por dominio |
| `view_active_contacts` | Ver contactos activos | Revisa los contactos que siguen activos. | Contactos filtrados por empresa |
| `open_contact` | Abrir contacto | Revisa la ficha completa. | Ficha del contacto |
| `complete_contact_profile` | Completar ficha | Faltan empresa y propietario. | Editar contacto |
| `complete_company_profile` | Completar ficha | Falta propietario, canal o sitio web. | Editar empresa |

### 5.4 Deduplicación de acciones

- Varias señales pueden apuntar a la misma pantalla. Se combinan en una sola acción y la descripción enumera los motivos: «Completar ficha — faltan empresa y propietario.»
- La acción «Abrir contacto» / «Abrir empresa» solo se añade si no hay otra acción que ya lleve a la ficha.
- El orden de las acciones sigue el orden de severidad de su señal principal.

### 5.5 Inglés

Reflejar las mismas cadenas en `crm/lang/en/crm.php`; usar `trans_choice` para el plural. Las etiquetas de interfaz viven en `lnx-ui/lang/en/components.php`; añadir `see_more`, `hide`, `no_signals` y `narrative_label`.

## 6. Orden de severidad

1. `archived_with_active_contacts` — integridad.
2. `duplicate_email` — riesgo de contacto equivocado.
3. `missing_channel` — no se puede contactar.
4. `archived_company` — contexto de cuenta.
5. `duplicate_website` — cuentas duplicadas.
6. `duplicate_name` — ambigüedad.
7. `missing_company` — segmentación.
8. `missing_owner` — responsabilidad.
9. `missing_website` — completitud baja.

Reglas:

- Ordenar observaciones por esta lista; empates por `name` alfabético para estabilidad.
- Las acciones heredan el orden de su señal.
- Si una acción combina varias señales, usa la severidad más alta.
- El brief y el insight muestran hasta 3 acciones + «Ver más».
- La narrativa prioriza la señal más severa.

## 7. Fallback determinista

- El brief siempre se renderiza sin llamada al modelo.
- Frase de fallback:
  - 0 señales: «No veo puntos de atención en este contacto.» / «... esta empresa.»
  - 1 señal: «Veo 1 punto de atención: {observación}»
  - 2+ señales: «Veo {N} puntos de atención. El más importante: {observación de la primera}.»
- La compone el presentador del módulo en el idioma del usuario. Nunca llama al modelo.
- La narrativa del modelo sustituye al fallback solo si pasa la verificación de grounding (§9.3).
- Si el modelo no está disponible, tarda más de 4 s o falla la verificación, el fallback se queda y el brief sigue siendo plenamente usable.

## 8. CTAs

### 8.1 Continuar en Sam

- Abre el panel de Sam con el contexto de la fila.
- Inicia una **conversación nueva** para no mezclar contextos (riesgo del roadmap).
- Precarga el mensaje con un prompt determinista derivado de las señales principales:
  - Faltantes: «Ayúdame a completar la ficha de {label}: {motivos}.»
  - Duplicados: «Revisa si {label} está duplicado: {motivo}.»
  - Empresa archivada: «Revisa la empresa {label}: está archivada y tiene {N} contactos activos.»
  - Sin señales: «Cuéntame qué puedo revisar de {label}.»
- La persona puede enviar con un clic; el mensaje no se envía solo (evita consumir cuota sin consentimiento).
- El prompt viaja con el contexto de fila (parámetro de URL o evento); el panel lo precarga en el campo de escritura. El brief lo aporta en `continuePrompt`.
- Si el actor no tiene `sam.use`, el CTA no se muestra.

### 8.2 Profundizar

- Abre la ficha del registro (contacto o empresa) en la misma pestaña, con `wire:navigate`.
- Si el insight no tiene URL de registro (no debería ocurrir), abre la lista filtrada.
- Es la vía para inspeccionar el registro completo y actuar manualmente.

### 8.3 Ver más

- Expande la lista de acciones en el mismo sitio (popover/hoja o tarjeta del brief). No navega.
- En el insight, el estado expandido se reinicia al cerrar. En el brief, se mantiene durante la sesión.

## 9. Narrativa del modelo (brief híbrido)

### 9.1 Entrada

- Tipo de registro (contacto/empresa), etiqueta y JSON de señales (`name`, `value`, `evidence`).
- Sin texto libre del registro más allá de la etiqueta. Sin valores de formulario, notas, correos ni teléfonos.
- Locale del actor.

### 9.2 Instrucciones del prompt

```
Eres Sam, el agente de la plataforma. Escribe un resumen breve del registro en foco.
Reglas:
- 2 a 4 frases, máximo 60 palabras.
- Usa solo las señales listadas. No inventes datos, causas ni consecuencias.
- No menciones nombres técnicos de señales.
- Ordena por gravedad: primero lo que más riesgo o fricción causa.
- No prometas acciones ni digas que ya hiciste algo.
- No repitas el nombre del registro más de una vez.
- Responde en {locale}.
Si no hay señales, di que no ves puntos de atención.
```

### 9.3 Verificación de grounding

- Rechazar la narrativa si:
  - Contiene un número que no está en los valores de las señales o en el conteo de señales.
  - Contiene una frase prohibida: «he actualizado», «he creado», «ya está hecho», «he borrado», «he enviado», «he archivado», «he restaurado», o sus equivalentes en inglés.
  - Menciona un campo que no está en las señales (por ejemplo, «facturación», «pipeline»).
- Si se rechaza, se usa el fallback. Registrar el rechazo para evaluación (sin guardar el texto completo de la narrativa; solo el motivo y la correlación).
- La narrativa no se persiste en el historial de la conversación; es estado de la tarjeta. Si se cachea, se cachea la narrativa saneada por registro/usuario.

### 9.4 Cuota y latencia

- Bucket de cuota separado del chat. Si se agota, fallback sin error visible.
- Timeout: 4 s. Pasado el timeout, fallback; si la narrativa llega después, se ignora.
- Caché de sesión: reutilizar la narrativa del mismo registro/usuario hasta que cambie el `updated_at` del registro. Recomendado para 1b; no bloqueante.

## 10. Contrato de datos

Brechas del andamiaje actual que deben cerrarse:

- `SamBrief` y `SamInsight` necesitan campos listos para mostrar:
  - `observations: list<string>` — frases traducidas para «Qué veo».
  - `actions: list<SamAction>` — objetos de acción, no cadenas.
- `SamAction`:
  ```php
  final class SamAction
  {
      public function __construct(
          public readonly string $key,
          public readonly string $label,
          public readonly string $description,
          public readonly string $url,
      ) {}
  }
  ```
- Mantener `signals: list<SamSignal>` para el prompt del modelo; la interfaz renderiza `observations`, no los nombres crudos de las señales.
- `SamBrief`:
  - `source`, `resource`, `id`, `label`
  - `signals` (crudas)
  - `observations` (mostrables)
  - `narrative` (modelo o fallback)
  - `actions` (`list<SamAction>`)
  - `evidence` (URL del registro)
  - `continuePrompt` (texto determinista para precargar el chat)
- `SamInsight`:
  - `source`, `resource`, `id`, `label`
  - `signals` (crudas)
  - `observations` (mostrables)
  - `actions` (`list<SamAction>`)
  - `continuePrompt` (texto determinista para precargar el chat)
  - `continueUrl`, `deepenUrl`
- El presentador en `crm` compone observaciones y acciones con las traducciones de `crm`. La interfaz (`lnx-ui`) solo renderiza.
- `SamSignal` se mantiene igual; no se le añade texto de presentación.

## 11. i18n

- Español primero; inglés espejo.
- Copy de señales y acciones en `crm/lang/{es,en}/crm.php` bajo `sam.signals.*` y `sam.actions.*`.
- Etiquetas de interfaz en `lnx-ui/lang/{es,en}/components.php` bajo `sam_brief.*`; añadir `see_more`, `hide`, `no_signals`, `narrative_label`.
- Pluralización con `trans_choice`.
- El prompt de la narrativa usa el locale del usuario; el fallback usa las mismas traducciones.
- Fechas y números con el locale de la app; sin formatos fijos.

## 12. Accesibilidad

- Tarjeta del brief: `role="region"` con `aria-label="Resumen de Sam"`. El botón de colapsar tiene `aria-expanded` y `aria-controls`.
- Lista de observaciones: `<ul>`; cada elemento con enlace de evidencia accesible.
- Acciones: enlaces con etiqueta visible; la descripción forma parte del texto del enlace o se asocia con `aria-describedby`.
- Popover del insight: el foco se mueve al abrirlo; `Esc` cierra y devuelve el foco al botón de fila. Sin trampa de foco; el resto de la página sigue usable.
- Contraste y movimiento reducido: seguir las convenciones existentes de `lnx-ui`.
- Anunciar la llegada de la narrativa con `aria-live="polite"` sin robar el foco.

## 13. Rendimiento y seguridad

- Insight bajo demanda; consultas acotadas; sin N+1 en el render de la lista. Objetivo p95 ≤ 300 ms.
- Brief determinista p95 ≤ 200 ms; narrativa asíncrona con timeout de 4 s.
- El presentador vuelve a resolver el registro y comprueba permisos; fuera de alcance → `null`.
- Las URL de evidencia deben ser rutas autorizadas; no hacen falta URL firmadas porque la persona ya tiene acceso.
- El prompt de la narrativa no incluye texto libre del registro; solo etiqueta, tipo y señales.
- Sin fuga entre registros: los conteos de duplicados se calculan dentro del alcance del actor (ya implementado en `crm`).
- Rate limit: bucket separado; el brief nunca bloquea el chat.

## 14. Criterios de aceptación

Mapeo a la rúbrica del roadmap:

- **Corrección:** cada observación se corresponde con una señal; los enlaces de evidencia resuelven a pantallas autorizadas; la narrativa no introduce números ni campos fuera de las señales.
- **No-obviedad:** el brief muestra al menos una señal que no está en las columnas de la lista (conteos de duplicados, empresa archivada, canal faltante).
- **Accionabilidad:** al menos una acción por brief/insight no vacío; las acciones llevan a una pantalla donde la persona puede actuar.
- **Confianza:** la lista determinista siempre está visible; la narrativa está etiquetada; no hay promesas; el fallback es tan útil como la versión del modelo.
- **Autorización:** un actor sin acceso no recibe brief/insight; ninguna señal se filtra.
- **Latencia:** insight p95 ≤ 300 ms; brief determinista p95 ≤ 200 ms; timeout de narrativa 4 s.
- **i18n:** es/en completos; singular/plural correctos.
- **Accesibilidad:** teclado, foco, `Esc` y anuncios pasan la revisión manual.

## 15. Ejemplos

### 15.1 Contacto con faltantes y duplicados

Ana Pérez, sin empresa, sin propietario, 2 contactos más con su correo.

- **Resumen:** «Ana Pérez no tiene empresa ni propietario asignados. Su correo aparece en 2 contactos más, así que conviene revisar si son duplicados antes de escribirle.»
- **Qué veo:**
  - Sin empresa asignada.
  - Sin propietario asignado.
  - Hay 2 contactos más con este correo.
- **Qué puedes hacer:**
  - Completar ficha — faltan empresa y propietario.
  - Comparar duplicados — evita escribir al contacto equivocado.
- **Continuar en Sam:** «Ayúdame a completar la ficha de Ana Pérez: no tiene empresa ni propietario.»
- **Profundizar:** abre la ficha de Ana Pérez.

### 15.2 Empresa archivada con contactos activos

Acme, archivada, 3 contactos activos.

- **Resumen:** «Acme está archivada, pero tiene 3 contactos activos. Revisa si corresponde restaurarla o reasignar esos contactos.»
- **Qué veo:**
  - La empresa está archivada y tiene 3 contactos activos.
- **Qué puedes hacer:**
  - Ver contactos activos — revisa los contactos que siguen activos.
  - Abrir empresa — revisa el estado de la cuenta.
- **Continuar en Sam:** «Revisa la empresa Acme: está archivada y tiene 3 contactos activos.»
- **Profundizar:** abre la ficha de Acme.

### 15.3 Registro sin señales

Contacto sin faltantes ni duplicados.

- **Resumen:** «No veo puntos de atención en este contacto.»
- **Qué veo:** vacío; se muestra «Sin puntos de atención».
- **Qué puedes hacer:** vacío; se muestra solo «Continuar en Sam».
- **Continuar en Sam:** «Cuéntame qué puedo revisar de {label}.»

## 16. Preguntas abiertas para el mantenedor

- ¿Se acepta «Resumen de Sam» como etiqueta visible, o se prefiere «Brief»?
- ¿«Profundizar» debe abrir la ficha del registro o el brief completo en el panel? (Recomendación: la ficha.)
- ¿«Continuar en Sam» debe iniciar siempre una conversación nueva? (Recomendación: sí, para no mezclar contextos.)
- ¿La narrativa del modelo se cachea por sesión en 1b o se espera a Fase 3? (Recomendación: caché de sesión.)
