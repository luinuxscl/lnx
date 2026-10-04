# Roadmap de prioridades

Última revisión: 2026-10-04. Priorización acordada con el mantenedor.

Este documento conserva el orden global de trabajo. GitHub es la fuente del estado y los criterios de aceptación de cada issue; los órdenes parciales escritos en ellas se concilian aquí. Las etapas no son compromisos de fecha. El esfuerzo es comparativo por alcance, no una estimación de duración.

## Próximo trabajo

Feedback y soporte funciona como módulo independiente. La issue [#34](https://github.com/luinuxscl/lnx/issues/34) permanece abierta: consumirá los eventos y operaciones autorizadas de soporte para integrar mensajería futura sin duplicar historiales.

Priorizar y abordar la mejora visual **#27** (o la siguiente issue acordada con el mantenedor). Las etapas 1 a 7 del roadmap inicial están completas, incluyendo la consulta con fuentes propios RAG (**#17**) y la estandarización del formato numérico con punto para miles y coma para decimales (**#31**).

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

## Decisiones de secuencia

- Nueva secuencia acordada: implementar soporte y feedback para usuarios, gestionado exclusivamente por root, antes de [#34 Mensajería interna](https://github.com/luinuxscl/lnx/issues/34) (abierta). Soporte funcionará inicialmente con su conversación y las notificaciones existentes, emitiendo eventos de creación, respuesta y cambio de estado. #34 incorporará mensajería entre usuarios, sistema e IA, envíos individuales/grupales de root/admin e integración con soporte mediante respuestas y acciones autorizadas, conservando un único historial. Esta secuencia no cambia la prioridad relativa de #27.

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

GitHub registra todas las etapas principales (1 a 7) cerradas. La mejora visual #27 sigue pendiente de priorización. Skills reutilizables (#22), notificaciones (#9), preferencias por usuario (#10), asistente de texto (#14), cuotas y consumo de IA (#13), base de IA (#12), servidor MCP de lectura (#21), dashboard por rol (#11), invitaciones (#4), suspensión (#5), historial administrativo (#6), checklist de puesta en marcha (#7), sesiones (#8), contexto de agentes (#18), diseño (#19), coherencia de instrucciones (#20), extracción documental (#16), base de conocimiento RAG (#17) y formato numérico (#31) están cerradas.

## Mantenimiento

Actualizar este documento durante el trabajo del proyecto cuando se cierre o reabra una issue del roadmap, cambien dependencias o alcance, aparezca un bloqueo relevante o el mantenedor cambie prioridades. Consultar el estado real en GitHub antes de actualizarlo; no interpretar un merge como cierre confirmado.

En cada actualización pertinente, ajustar el próximo trabajo, el estado y las etapas afectadas, cambiar la fecha de revisión y dejar una breve razón si cambia el orden. Conservar los criterios detallados en las issues para evitar duplicarlos. No implica monitorización periódica ni ejecución automática del backlog.
