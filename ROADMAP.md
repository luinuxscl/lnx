# Roadmap de prioridades

Última revisión: 2026-10-01. Priorización acordada con el mantenedor.

Este documento conserva el orden global de trabajo. GitHub es la fuente del estado y los criterios de aceptación de cada issue; los órdenes parciales escritos en ellas se concilian aquí. Las etapas no son compromisos de fecha. El esfuerzo es comparativo por alcance, no una estimación de duración.

## Próximo trabajo

Comenzar por **#7**. Primer hito: completar **#7 → #8 → #11**, con checklist de puesta en marcha, gestión de sesiones y dashboard útil por rol.

## Orden acordado

| Etapa | Issues, en orden | Resultado y motivo | Esfuerzo relativo |
| --- | --- | --- | --- |
| 1. Completar la operación del kit | [#7 Checklist](https://github.com/luinuxscl/lnx/issues/7) → [#8 Sesiones](https://github.com/luinuxscl/lnx/issues/8) → [#11 Dashboard](https://github.com/luinuxscl/lnx/issues/11) | Facilitar despliegues, retirar acceso desde otros dispositivos y ofrecer una portada útil. #7 desbloquea la entrega completa de #11. | Medio–alto |
| 2. Conectar agentes con la aplicación | [#21 MCP de lectura](https://github.com/luinuxscl/lnx/issues/21) | Validar consultas autorizadas desde un cliente real. Su dependencia #18 está resuelta; no depende de consumir LLMs. | Alto |
| 3. Entregar la primera función de IA | [#12 Integración](https://github.com/luinuxscl/lnx/issues/12) → [#13 Cuotas y consumo](https://github.com/luinuxscl/lnx/issues/13) → [#14 Asistente de texto](https://github.com/luinuxscl/lnx/issues/14) | Configurar OpenRouter, controlar consumo y mejorar, resumir o traducir con aceptación humana. Mantener esta secuencia como una entrega funcional completa. | Alto |
| 4. Mejorar la experiencia cotidiana | [#10 Preferencias](https://github.com/luinuxscl/lnx/issues/10) → [#9 Notificaciones](https://github.com/luinuxscl/lnx/issues/9) | Unificar idioma, fechas y apariencia antes de añadir la bandeja persistente. Es una conveniencia de implementación, no una dependencia obligatoria. | Medio |
| 5. Consolidar procedimientos probados | [#22 Skills reutilizables](https://github.com/luinuxscl/lnx/issues/22) | Extraer procedimientos de entregas reales de funcionalidad, MCP y verificación. Sus dependencias #18–#20 están cerradas; queda #21. | Medio |
| 6. Llevar mejoras a proyectos derivados | [#23 Versionado y actualización](https://github.com/luinuxscl/lnx/issues/23) | Tras #22, validar actualizaciones, conflictos y reversión en un proyecto piloto autorizado antes de distribuir el núcleo. | Alto |
| 7. Ampliar IA según demanda | [#15 Chat](https://github.com/luinuxscl/lnx/issues/15) y [#16 Extracción documental](https://github.com/luinuxscl/lnx/issues/16) | Elegir primero el que tenga un caso concreto en un proyecto derivado. Ambos requieren #12 y #13; son independientes entre sí. | Alto por módulo |
| 8. Incorporar conocimiento propio | [#17 Consulta con fuentes](https://github.com/luinuxscl/lnx/issues/17) | Requiere #12, #13 y #15. Validar con documentos reales la recuperación, los permisos y la calidad de las referencias; no requiere #16. | Muy alto |

## Decisiones de secuencia

- Adelantar #11 respecto de su posición en el backlog original: invitaciones (#4) ya está resuelta y basta completar #7 para cubrir sus dependencias. El resumen de consumo de IA se incorpora cuando exista #13, sin retrasar el dashboard inicial.
- No habilitar funciones de IA para usuarios antes de disponer de los controles de #13.
- No convertir la distribución del kit (#23) en requisito para implementar funcionalidades. Si un cliente necesita chat o documentos, se puede adelantar ese módulo respecto de #23 conservando sus dependencias.
- #9 es una integración opcional para avisar del procesamiento de documentos; no bloquea #16.

## Estado de partida

En la última revisión había 14 issues abiertas: #7–#17 y #21–#23. Invitaciones (#4), suspensión (#5), historial administrativo (#6), contexto de agentes (#18), diseño (#19) y coherencia de instrucciones (#20) estaban cerradas.

## Mantenimiento

Actualizar este documento durante el trabajo del proyecto cuando se cierre o reabra una issue del roadmap, cambien dependencias o alcance, aparezca un bloqueo relevante o el mantenedor cambie prioridades. Consultar el estado real en GitHub antes de actualizarlo; no interpretar un merge como cierre confirmado.

En cada actualización pertinente, ajustar el próximo trabajo, el estado y las etapas afectadas, cambiar la fecha de revisión y dejar una breve razón si cambia el orden. Conservar los criterios detallados en las issues para evitar duplicarlos. No implica monitorización periódica ni ejecución automática del backlog.
