---
paths:
  - 'resources/views/livewire/**'
---

# Livewire

## Criterios para widgets de métricas (statistics-panel / stat-card)
El dashboard responde «¿qué requiere mi atención ahora?»: solo métricas accionables y como máximo una fila de cifras por panel. El cuadro completo de KPIs vive en la página de lista correspondiente, donde cada tarjeta actúa como filtro rápido (no duplicar las cifras en ambos sitios).
Si el título del panel ya nombra el sujeto, la etiqueta va sin el sustantivo («Totales», «Suspendidos», «Sin verificar») y el texto completo se conserva en aria-label («Usuarios suspendidos»). Nunca repetir el sujeto en cada cifra.
No crear tarjetas-enlace a un destino que el panel o el sidebar ya ofrecen: si la métrica enlaza a una pantalla, no añadir además una tool-card al mismo lugar. Las acciones de un panel van en su slot actions (p. ej. «Ver usuarios» + «Invitar usuario»).
Un color, un significado: cada tono de stat-card identifica una sola métrica en todas las pantallas (accent para invitaciones pendientes, rose para suspendidos, amber solo para sin verificar).
Los contadores compartidos entre pantallas se calculan en una acción única del proyecto (en crm-app, la acción `CountUsers`) con una sola consulta agregada; no duplicar SQL ni recalcular en cada vista.
