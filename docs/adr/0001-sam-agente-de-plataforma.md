# ADR 0001 — Sam como agente de plataforma

- **Estado:** aceptado (2026-11-03).
- **Fecha:** 2026-11-03.
- **Ámbito:** kit `lnx`, paquete `luinuxscl/sam`, módulos (empezando por `crm`) y apps derivadas.
- **Matiza:** la decisión de licencia del 2026-10-25 («Sam vive en el producto, nunca en el kit») y la enmienda del 2026-11-03 del ADR 0002 del módulo CRM, que trataba la UI del agente como bloque 2 del CRM.

## Contexto

Sam existe hoy dentro del módulo CRM como una superficie propia (`/crm/sam`): un hilo por actor, cinco lecturas autorizadas y ocho propuestas draft-first. Cumple el contrato —no confirma, no borra, no fusiona, no envía— y deja auditoría visible en `/crm/agent-activity`. Pero es una **pantalla aislada**: no conoce otros módulos, no sabe en qué pantalla está el usuario y obliga a repetir el contexto en cada petición.

La visión de producto es otra. Sam es la voz de LikePlatform, interactúa con los usuarios finales de la instancia, tiene nombre fijo, se declara IA en el primer contacto y **nunca tiene más permiso que la persona con la que conversa**. Su promesa es la de la landing: «Tu equipo pide, Sam propone, tú confirmas. Todo queda registrado.»

El objetivo de producto es quitar fricción. Los CRM de referencia —HubSpot, Defontana, Odoo— son potentes y ruidosos: muchas opciones, muchas pantallas y configuración por todas partes. Sam debe permitir operar el CRM en lenguaje natural, con el contexto de la pantalla ya cargado y con una confirmación humana clara. Una vista de chat al margen no consigue eso.

Técnicamente, el CRM ya duplica lo que el kit tiene resuelto: conversaciones, streaming, contexto de página, herramientas y citas viven en `/chat`. Mantener a Sam dentro del módulo obliga a reconstruir esa infraestructura y a repetirla en cada módulo que quiera un agente. La conclusión es que **el agente es una capacidad de la plataforma y los módulos aportan conocimiento y operaciones**.

Hay dos restricciones que condicionan la solución:

1. **Licencia.** El kit `lnx` es MIT; el producto —`crm`, `crm-app` y Sam— es AGPL-3.0-or-later. La capacidad de agente puede vivir en la base, pero la identidad y la experiencia de Sam son producto.
2. **Distribución.** `lnx` es un andamio, no una dependencia: las apps derivadas copian el código y `scripts/update-agent-kit.py` no sincroniza `app/`. Todo cambio en el runtime debe portarse de forma explícita a cada app derivada.

## Decisión

1. **Runtime y contrato en `lnx` (MIT).** La base aloja el registro de contribuciones, los DTO de contexto y propuesta, el runtime del agente, el almacén de conversaciones, la auditoría, las cuotas y la puerta de confirmación. Vive en `App\Ai\Sam\*` y no conoce la marca ni el dominio de ningún módulo.

2. **Identidad y superficie en `luinuxscl/sam` (AGPL-3.0-or-later).** El paquete aporta el nombre fijo, la declaración literal, la persona, el panel, la tarjeta de propuesta, el permiso `sam.use` y su configuración. Registra un perfil de Sam en el runtime. No es un paquete autónomo: asume una app derivada de lnx.

3. **Contrato de contribución.** Un módulo declara un `SamContributor` con conocimiento, lecturas, propuestas, contexto de pantalla, sugerencias y presentador de borradores. Nada se expone por estar el paquete en `vendor/`; la declaración es explícita y se valida. El contrato queda en [`CONTRATO-MODULO.md`](../../CONTRATO-MODULO.md) §3.9.

4. **Sam generaliza el chat.** Hay una sola superficie de agente. `/chat` se retira de la navegación (con redirección temporal al panel) y sus conversaciones pasan a Sam. Se conservan conversaciones, streaming, contexto de página, herramientas, citas, búsqueda en el Centro de ayuda y bases de conocimiento como datos adjuntos. Se retiran de la superficie de usuario el prompt de sistema y el modelo por conversación y los presets de asistente: la identidad de Sam es fija.

5. **Superficie integrada, no una vista.** Sam se invoca desde el header en cualquier pantalla, con atajo de teclado y panel lateral persistente. El panel adjunta el contexto de la pantalla actual (registro, filtros, pestaña) y muestra las propuestas como tarjetas inline con diff, estado y caducidad. `/crm/sam` desaparece como destino.

6. **Confirmación inline con frase.** La tarjeta permite confirmar el borrador en el panel escribiendo la frase de un solo uso. La ejecución la realiza la acción de confirmación del módulo; Sam nunca escribe. La pantalla de borradores del módulo queda como alternativa. La frase se muestra una vez y no se persiste en el historial.

7. **Datos.** `ai_conversations` gana `profile` (`chat`/`sam`) y `ai_messages` gana `correlation_id`. Las conversaciones actuales se importan como historial de Sam y dejan de aplicar su prompt y modelo personalizados. `crm_sam_messages` se importa con un comando del CRM a una conversación `profile=sam` por actor; la tabla vieja queda en solo lectura y se retira en una versión mayor.

8. **Permisos.** `sam.use` protege la superficie. Las herramientas de cada módulo vuelven a comprobar sus propios permisos y su alcance. `crm.use_sam` se retira; los roles que hoy lo reciben reciben `sam.use`.

9. **Primera rebanada vertical.** Panel global + una lectura del CRM + una propuesta del CRM + tarjeta inline + auditoría. Fuera de la rebanada: navegar o aplicar filtros por instrucción, cross-módulo, exportación de auditoría, acciones masivas y cambios de streaming.

10. **Cada cambio en su repositorio.** El runtime y el contrato en `lnx`; la identidad y la superficie en `sam`; las piezas visuales en `lnx-ui`; el conocimiento y las operaciones en `crm`; la integración y el despliegue en `crm-app`. El runtime de `lnx` se porta a `crm-app` de forma explícita.

## Consecuencias

- **La frase pública se mantiene** —«El kit es MIT. El producto es AGPL»— pero cambia su lectura: la capacidad de agente es base; la identidad y la experiencia de Sam son producto. Hay que corregir la nota de licencias y el manual de plataforma del vault, y el `AGENTS.md` del CRM.
- **Cambios incompatibles:** `/chat` sale de la navegación, `crm.use_sam` desaparece, `/crm/sam` deja de existir y `crm_sam_messages` deja de escribirse. El kit necesita una versión mayor si retira `/chat`; el módulo CRM, una versión mayor si retira su superficie y su permiso.
- **La enmienda del bloque 2 del ADR 0002 del CRM queda superada.** El roadmap del CRM vuelve a su numeración de producto (2 = vistas guardadas) y solo conserva la contribución del módulo a Sam; el trabajo de plataforma se sigue en `lnx`.
- **La suite de `SamTest` del CRM se parte:** identidad y declaración pasan al paquete Sam; herramientas, conocimiento y propuestas quedan en el CRM.
- **La auditoría gana una pieza común:** el runtime registra lecturas y propuestas con correlación; el módulo conserva la auditoría de borradores y confirmaciones. La vista de actividad debe poder reconstruir el ciclo completo.
- **Riesgo de producto:** la tentación es construir un chat bonito. La medida es una tarea real del CRM resuelta sin salir de la pantalla, con menos pasos y sin filtrar datos.
- **Riesgo de seguridad:** la frase de confirmación no puede quedar en el historial ni en el estado público de Livewire. Necesita revisión antes de implementar la tarjeta.
- **Riesgo de distribución:** el runtime de `lnx` no llega solo a `crm-app`. Si aparecen más apps derivadas, conviene evaluar su extracción a un paquete MIT.

## Alternativas consideradas

- **Mantener a Sam dentro del CRM.** Descartada: impide que sea el agente de la plataforma, duplica el chat y obliga a repetir la integración en cada módulo.
- **Poner todo Sam en `lnx` (MIT).** Descartada: convierte en MIT la identidad y la experiencia de un producto AGPL y diluye la frontera kit/producto.
- **Poner todo Sam en un paquete AGPL separado.** Descartada: la base no sabría alojar agentes ni los módulos tendrían un contrato estable donde contribuir.
- **Mantener `/chat` y Sam como dos agentes.** Descartada: dos superficies confunden al usuario y duplican conversaciones, permisos y auditoría.
- **Confirmar solo en la pantalla de borradores.** Descartada: añade fricción justo en el paso que da valor; la frase de un solo uso ya conserva la puerta humana.
- **Aprobar un rediseño completo antes de la primera rebanada.** Descartada: retrasa la validación de la hipótesis —que Sam reduce la fricción— sin evidencia.

## Cuestiones abiertas

- Cómo se muestra y se copia la frase de confirmación sin persistirla ni exponerla en el estado del componente.
- Si `viewer` recibe `sam.use` o queda fuera como hoy.
- Si las bases de conocimiento se pueden adjuntar a conversaciones de Sam o solo alimentan la ayuda del producto.
- Exportación y reconstrucción de la auditoría completa.
- Acciones de UI (navegar, aplicar filtros) y continuidad entre módulos.
- Cómo se mide la fricción: tarea completada, pasos y tiempo, sin sustituir la revisión manual.

## Aprobación

Aceptado por el mantenedor el 2026-11-03:

- [x] El runtime y el contrato viven en `lnx` (MIT); la identidad y la superficie de Sam, en `luinuxscl/sam` (AGPL).
- [x] Sam deja de ser una pantalla del CRM y generaliza el chat.
- [x] La confirmación es inline con la frase de un solo uso.
- [x] Los módulos contribuyen conocimiento y operaciones mediante un contrato explícito.
- [x] La primera entrega es una rebanada vertical, no un rediseño completo.
