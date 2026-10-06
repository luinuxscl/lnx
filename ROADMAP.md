# Roadmap de prioridades

Última revisión: 2026-10-05. Priorización acordada con el mantenedor.

Este documento conserva el orden global de trabajo. GitHub es la fuente del estado y los criterios de aceptación de cada issue; los órdenes parciales escritos en ellas se concilian aquí. Las etapas no son compromisos de fecha. El esfuerzo es comparativo por alcance, no una estimación de duración.

## Próximo trabajo

La **Etapa 8** ([#34 Mensajería interna entre usuarios, sistema e IA integrada con soporte](https://github.com/luinuxscl/lnx/issues/34)) y la **Etapa 9: Modernización visual y UI/UX** ([#27](https://github.com/luinuxscl/lnx/issues/27) y [#35](https://github.com/luinuxscl/lnx/issues/35)) han sido completadas e integradas con éxito. No queda ninguna etapa activa: el próximo trabajo queda a la espera de nueva priorización del mantenedor.

## Orden acordado

| Etapa | Issues, en orden | Resultado y motivo | Esfuerzo relativo |
| --- | --- | --- | --- |
| 1. Conectar agentes con la aplicación | [#21 MCP de lectura](https://github.com/luinuxscl/lnx/issues/21) (cerrada) | Validar consultas autorizadas desde un cliente real. Dependencia #18 resuelta; no depende de consumir LLMs. | Alto |
| 2. Entregar la primera función de IA | [#12 Integración](https://github.com/luinuxscl/lnx/issues/12) (cerrada) → [#13 Cuotas y consumo](https://github.com/luinuxscl/lnx/issues/13) (cerrada) → [#14 Asistente de texto](https://github.com/luinuxscl/lnx/issues/14) (cerrada) | Configurar OpenRouter, controlar consumo y mejorar, resumir o traducir con aceptación humana. Secuencia completada como una entrega funcional. | Alto |
| 3. Mejorar la experiencia cotidiana | [#10 Preferencias](https://github.com/luinuxscl/lnx/issues/10) (cerrada) → [#9 Notificaciones](https://github.com/luinuxscl/lnx/issues/9) (cerrada) | Preferencias por cuenta y bandeja persistente integradas. | Medio |
| 4. Consolidar procedimientos probados | [#22 Skills reutilizables](https://github.com/luinuxscl/lnx/issues/22) (cerrada) | Extraer procedimientos de entregas reales de funcionalidad, MCP y verificación. Sus dependencias #18–#21 están cerradas. | Medio |
| 5. Llevar mejoras a proyectos derivados | [#23 Versionado y actualización](https://github.com/luinuxscl/lnx/issues/23) (cerrada) | Manifiesto versionado, herramienta de actualización determinista con previsualización, preservación de personalizaciones locales, reversión y pruebas. | Alto |
| 6. Ampliar IA según demanda | [#15 Chat](https://github.com/luinuxscl/lnx/issues/15) (cerrada) y [#16 Extracción documental](https://github.com/luinuxscl/lnx/issues/16) (cerrada) | #15 y #16 completados. | Alto por módulo |
| 7. Incorporar conocimiento propio | [#17 Consulta con fuentes](https://github.com/luinuxscl/lnx/issues/17) (cerrada) y [#31 Formato numérico](https://github.com/luinuxscl/lnx/issues/31) (cerrada) | #17 (módulo RAG de base de conocimiento con embeddings) y #31 (formato numérico regional) completados y cerrados. | Muy alto |
| 8. Mensajería interna y colaboración | [#34 Mensajería interna](https://github.com/luinuxscl/lnx/issues/34) (cerrada) | Habilitar comunicación directa entre usuarios, avisos de sistema y agentes de IA autorizados, integrando eventos y respuestas de soporte. Prerrequisito de soporte independiente completado. | Alto |
| 9. Modernización visual y UI/UX | [#27 Diseño y dashboard](https://github.com/luinuxscl/lnx/issues/27) (cerrada) y [#35 UI/UX de soporte](https://github.com/luinuxscl/lnx/issues/35) (cerrada) | Unificar la jerarquía visual del dashboard por rol y vistas clave (#27) y pulir el flujo de soporte y tickets (#35) con componentes consolidados y estados claros. | Medio-Alto |

## Decisiones de secuencia

- #34 se completó: modelos `InternalConversation`, `InternalConversationParticipant`, `InternalMessage`, `MessagingService`, bypass de `root` acotado en `AppServiceProvider`, vistas bilingües `/messages`, notificaciones en base de datos, integración bidireccional con eventos de soporte y suite de pruebas Pest.
- Etapa 9 completada y cerrada (#27 y #35): rediseño de la interfaz de mensajería con búsqueda en conversación, auto-scroll, agrupación de mensajes, compositor multilínea y accesibilidad; modernización visual del dashboard y vistas principales (#27) y del circuito de tickets de soporte (#35). Cierre confirmado por el mantenedor tras integrar la entrega en `main`.
- Secuencia acordada (Ruta B, 2026-10-04): Se priorizó la funcionalidad de comunicación en la Etapa 8 ([#34](https://github.com/luinuxscl/lnx/issues/34)). El módulo base de soporte y feedback se encuentra completado e integrado en `develop`. La modernización visual y de experiencia de usuario se agrupa en la Etapa 9 ([#27](https://github.com/luinuxscl/lnx/issues/27) y [#35](https://github.com/luinuxscl/lnx/issues/35)).
- Soporte funciona con su conversación y notificaciones existentes, emitiendo eventos de creación, respuesta y cambio de estado. #34 incorporó mensajería entre usuarios, sistema e IA, envíos individuales/grupales de root/admin e integración con soporte mediante respuestas y acciones autorizadas, conservando un único historial.

- #31 se completó: `Number::useLocale('es')` en `AppServiceProvider`, helper `App\Support\NumberFormat`, vistas y componentes numéricos actualizados, suite de pruebas Pest y documentación en `README.md`.
- #17 se completó: ingesta y chunking de documentos de conocimiento, almacenamiento vectorial integrado con OpenRouter embeddings, herramienta RAG `KnowledgeSearchTool`, filtrado por policies, respuestas con citas verificables, interfaz bilingüe `/knowledge`, suite de pruebas completada y documentación en `README.md`.
- #16 se completó: almacenamiento de archivos privados, extracción de texto plano/PDF, procesamiento con `AiGateway` en la aplicación `document_extraction`, validación de esquema con alertas por campos incompletos, revisión humana y confirmación con emisión de `DocumentExtractionConfirmed`, suite de pruebas Pest completada y documentación en `README.md`.
- #15 se completó: conversaciones privadas por usuario, aislamiento por `AiConversationPolicy`, `AiGateway::stream()` con cuotas y registro de consumo, `ProductHelpSearchTool` autorizada, componente Livewire bilingüe `/chat`, suite de pruebas pasadas y documentación actualizada.

- #23 se completó: manifiesto `.ai/kit-manifest.json`, script `scripts/update-agent-kit.py` con previsualización, preservación de personalizaciones locales y reversión, skill `update-agent-kit`, suite de pruebas automatizadas y actualización de la documentación del kit.
- #22 se integró y cerró: skills versionadas `implement-feature`, `expose-mcp-operation` y `verify-delivery` creadas en `.ai/skills/`, documentadas en `README.md` y sincronizadas en `.agents/skills/`. Completa la etapa 4 y habilita la etapa 5 (#23).
- #11 se integró y cerró tras resolver sus dependencias #4, #7 y #8. El resumen de consumo de IA del dashboard quedó cubierto al cerrar #13.
- #21 se integró y cerró: servidor MCP de lectura con transporte web, tokens revocables, autorización por policies y auditoría. Habilita el trabajo de #22 sin depender de consumir LLMs.
- #12 se integró y cerró: base de IA con Laravel AI SDK y OpenRouter, allowlist de modelos con capacidades validada en servidor y prueba de conexión root sin exponer la clave. Habilitó #13.
- #13 se integró y cerró: cuotas por usuario y aplicación con reserva atómica antes de llamar al proveedor, registro de consumo con coste reportado/estimado/desconocido, panel root en `/admin/ai/usage`, resumen en el dashboard y purga `ai:prune-usage`. Habilitó #14.
- #14 se integró y cerró: asistente de texto reutilizable (mejorar, resumir, traducir) sobre la capa de IA, con aceptación humana, límites y registro de consumo comunes, demo root activable en `/admin/ai/text-assistant` y README con el ejemplo de integración. Completa la primera entrega de IA y habilita la etapa 3.
- #10 se integró y cerró: idioma, zona horaria, tema y modo guardados por cuenta y aplicados al iniciar sesión y al renderizar fechas, con validación en servidor, comportamiento local para visitantes y navegación Livewire estable. Habilita #9.
- #9 se integró y cerró: bandeja persistente con contador, lectura individual y masiva, y avisos de cambios administrativos efectivos de rol. Los criterios se comprobaron sobre la implementación integrada y la suite completa pasó.
- Las funciones de IA ya pueden habilitarse para usuarios: los controles de #13 están disponibles.
- No convertir la distribución del kit (#23) en requisito para implementar funcionalidades. Si un cliente necesita chat o documentos, se puede adelantar ese módulo respecto de #23 conservando sus dependencias.
- #9 no bloquea #16; su primer evento es el cambio administrativo de rol, no el procesamiento de documentos.

## Estado actual

GitHub registra todas las etapas principales (1 a 9) cerradas, incluida la modernización visual y de UI/UX (Etapa 9: [#27](https://github.com/luinuxscl/lnx/issues/27) y [#35](https://github.com/luinuxscl/lnx/issues/35)). No hay trabajo de roadmap activo; el próximo trabajo queda a la espera de nueva priorización del mantenedor.

## Mantenimiento

Actualizar este documento durante el trabajo del proyecto cuando se cierre o reabra una issue del roadmap, cambien dependencias o alcance, aparezca un bloqueo relevante o el mantenedor cambie prioridades. Consultar el estado real en GitHub antes de actualizarlo; no interpretar un merge como cierre confirmado.

En cada actualización pertinente, ajustar el próximo trabajo, el estado y las etapas afectadas, cambiar la fecha de revisión y dejar una breve razón si cambia el orden. Conservar los criterios detallados en las issues para evitar duplicarlos. No implica monitorización periódica ni ejecución automática del backlog.
