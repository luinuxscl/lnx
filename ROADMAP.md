# Roadmap de prioridades

Última revisión: 2026-10-02. Priorización acordada con el mantenedor.

Este documento conserva el orden global de trabajo. GitHub es la fuente del estado y los criterios de aceptación de cada issue; los órdenes parciales escritos en ellas se concilian aquí. Las etapas no son compromisos de fecha. El esfuerzo es comparativo por alcance, no una estimación de duración.

## Próximo trabajo

Continuar con **#10** (sincronizar idioma, zona horaria y apariencia por usuario), primera de la etapa 3; le sigue #9 (centro de notificaciones). Es una conveniencia de implementación, no una dependencia obligatoria.

#14 (asistente de texto reutilizable con OpenRouter) se integró en `develop` y `main` y se cerró: agente y componente reutilizables con aceptación humana, límites y registro de consumo comunes, demo root activable y README con el ejemplo de integración. #13 (cuotas y consumo de IA) y #12 (base de IA) siguen cerradas, junto con #21 (MCP de lectura).

## Orden acordado

| Etapa | Issues, en orden | Resultado y motivo | Esfuerzo relativo |
| --- | --- | --- | --- |
| 1. Conectar agentes con la aplicación | [#21 MCP de lectura](https://github.com/luinuxscl/lnx/issues/21) (cerrada) | Validar consultas autorizadas desde un cliente real. Dependencia #18 resuelta; no depende de consumir LLMs. | Alto |
| 2. Entregar la primera función de IA | [#12 Integración](https://github.com/luinuxscl/lnx/issues/12) (cerrada) → [#13 Cuotas y consumo](https://github.com/luinuxscl/lnx/issues/13) (cerrada) → [#14 Asistente de texto](https://github.com/luinuxscl/lnx/issues/14) (cerrada) | Configurar OpenRouter, controlar consumo y mejorar, resumir o traducir con aceptación humana. Secuencia completada como una entrega funcional. | Alto |
| 3. Mejorar la experiencia cotidiana | [#10 Preferencias](https://github.com/luinuxscl/lnx/issues/10) → [#9 Notificaciones](https://github.com/luinuxscl/lnx/issues/9) | Unificar idioma, fechas y apariencia antes de añadir la bandeja persistente. Es una conveniencia de implementación, no una dependencia obligatoria. | Medio |
| 4. Consolidar procedimientos probados | [#22 Skills reutilizables](https://github.com/luinuxscl/lnx/issues/22) | Extraer procedimientos de entregas reales de funcionalidad, MCP y verificación. Sus dependencias #18–#21 están cerradas. | Medio |
| 5. Llevar mejoras a proyectos derivados | [#23 Versionado y actualización](https://github.com/luinuxscl/lnx/issues/23) | Tras #22, validar actualizaciones, conflictos y reversión en un proyecto piloto autorizado antes de distribuir el núcleo. | Alto |
| 6. Ampliar IA según demanda | [#15 Chat](https://github.com/luinuxscl/lnx/issues/15) y [#16 Extracción documental](https://github.com/luinuxscl/lnx/issues/16) | Elegir primero el que tenga un caso concreto en un proyecto derivado. Ambos requieren #12 y #13; son independientes entre sí. | Alto por módulo |
| 7. Incorporar conocimiento propio | [#17 Consulta con fuentes](https://github.com/luinuxscl/lnx/issues/17) | Requiere #12, #13 y #15. Validar con documentos reales la recuperación, los permisos y la calidad de las referencias; no requiere #16. | Muy alto |

## Decisiones de secuencia

- #11 se integró y cerró tras resolver sus dependencias #4, #7 y #8. El resumen de consumo de IA del dashboard quedó cubierto al cerrar #13.
- #21 se integró y cerró: servidor MCP de lectura con transporte web, tokens revocables, autorización por policies y auditoría. Habilita el trabajo de #22 sin depender de consumir LLMs.
- #12 se integró y cerró: base de IA con Laravel AI SDK y OpenRouter, allowlist de modelos con capacidades validada en servidor y prueba de conexión root sin exponer la clave. Habilitó #13.
- #13 se integró y cerró: cuotas por usuario y aplicación con reserva atómica antes de llamar al proveedor, registro de consumo con coste reportado/estimado/desconocido, panel root en `/admin/ai/usage`, resumen en el dashboard y purga `ai:prune-usage`. Habilitó #14.
- #14 se integró y cerró: asistente de texto reutilizable (mejorar, resumir, traducir) sobre la capa de IA, con aceptación humana, límites y registro de consumo comunes, demo root activable en `/admin/ai/text-assistant` y README con el ejemplo de integración. Completa la primera entrega de IA y habilita la etapa 3.
- Las funciones de IA ya pueden habilitarse para usuarios: los controles de #13 están disponibles.
- No convertir la distribución del kit (#23) en requisito para implementar funcionalidades. Si un cliente necesita chat o documentos, se puede adelantar ese módulo respecto de #23 conservando sus dependencias.
- #9 es una integración opcional para avisar del procesamiento de documentos; no bloquea #16.

## Estado de partida

En la última revisión hay 8 issues abiertas: #9–#10, #15–#17, #22–#23 y #27. La mejora visual #27 está pendiente de priorización y no altera por ahora el próximo trabajo #10. Asistente de texto (#14), cuotas y consumo de IA (#13), base de IA (#12), servidor MCP de lectura (#21), dashboard por rol (#11), invitaciones (#4), suspensión (#5), historial administrativo (#6), checklist de puesta en marcha (#7), sesiones (#8), contexto de agentes (#18), diseño (#19) y coherencia de instrucciones (#20) están cerradas.

## Mantenimiento

Actualizar este documento durante el trabajo del proyecto cuando se cierre o reabra una issue del roadmap, cambien dependencias o alcance, aparezca un bloqueo relevante o el mantenedor cambie prioridades. Consultar el estado real en GitHub antes de actualizarlo; no interpretar un merge como cierre confirmado.

En cada actualización pertinente, ajustar el próximo trabajo, el estado y las etapas afectadas, cambiar la fecha de revisión y dejar una breve razón si cambia el orden. Conservar los criterios detallados en las issues para evitar duplicarlos. No implica monitorización periódica ni ejecución automática del backlog.
