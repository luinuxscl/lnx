# Fundamentos visuales para componentes de lnx

Esta referencia registra decisiones reutilizables al aprobar componentes de la biblioteca. `resources/css/app.css` define los tokens que ejecuta la aplicación; este archivo describe cómo usarlos. Comprueba ambos antes de crear una variante nueva.

## Tipografía y títulos

| Uso | Base existente | Ejemplo |
| --- | --- | --- |
| Fuente de interfaz | `--font-sans` usa Instrument Sans y fuentes de sistema como respaldo. | `resources/css/app.css` |
| Título de página | `x-lnx-ui::page-header`; rinde un único `flux:heading` con `level="1"`, el texto visible se traduce. No crear `<h1>` a mano. | `x-lnx-ui::page-header` |
| Título de sección | `flux:heading` con `level="2"`. | `resources/views/livewire/dashboard.blade.php` |
| Título dentro de tarjeta | `flux:heading` con `level="3"`. | `resources/views/livewire/dashboard.blade.php` |
| Título de panel de métricas | `flux:heading` con `level="2"` y `size="lg"`, para distinguirlo del valor de cada métrica sin dominar la tarjeta. | `x-lnx-ui::statistics-panel` |
| Texto de apoyo | `flux:text`; usa un tamaño menor solo si la información es secundaria. | `resources/views/livewire/dashboard.blade.php` |
| Subtítulo de una tarjeta de métrica | `flux:text` con `text-sm` bajo el título para el periodo o contexto («Last week»); el título usa `flux:heading` con `level="3"`. | `x-lnx-ui::chart-card` |

## Espaciado y composición

| Relación | Base existente | Ejemplo |
| --- | --- | --- |
| Controles o iconos cercanos | `gap-2`. | `resources/views/components/desktop-user-menu.blade.php` |
| Acciones y tarjetas próximas | `gap-4`. | `resources/views/livewire/dashboard.blade.php` |
| Secciones o grupos de campos | `mt-6`, `space-y-6` o `gap-6` según el contenedor. | `resources/views/livewire/dashboard.blade.php` |
| Grilla de tarjetas | El padre define columnas responsivas y separación; el componente no fija su ancho. | `resources/views/livewire/dashboard.blade.php` |
| Cabecera y métricas de un panel | `space-y-4` entre cabecera y grilla; el panel contiene una grilla de 12 columnas con `gap-4` y cada métrica recibe su `col-span-*` desde la invocación. | `x-lnx-ui::statistics-panel` |
| Métricas como tarjetas individuales | Un contenedor externo usa `grid grid-cols-12 gap-4` y asigna el `col-span-*` a cada `x-stat-card`; la tarjeta reutiliza `x-stat`. | `x-lnx-ui::stat-card` |
| Tarjetas de tendencia | El padre asigna `col-span-12 md:col-span-6 xl:col-span-4` para mostrar una, dos o tres tarjetas por fila según el ancho. | `x-lnx-ui::trend-card` |
| Tarjetas-enlace a un área | `x-tool-card` envuelve un `flux:card` completo en un `<a>` de bloque; el contenedor asigna `col-span-12 sm:col-span-6 xl:col-span-4` (tres por fila) o `xl:col-span-3` (cuatro por fila). | `x-lnx-ui::tool-card` |
| Tarjetas de métrica (con gráfico o KPI) | El padre asigna `col-span-12 md:col-span-6 xl:col-span-3` para una, dos o cuatro por fila; el pie con el valor usa `mt-auto` para alinearse al fondo de la tarjeta. | `x-lnx-ui::chart-card`, `x-lnx-ui::metric-card` |
| Cabecera de tarjeta con etiqueta y badges | Fila con `flex-wrap`: la etiqueta no se trunca y los badges usan `ms-auto` para alinearse a la derecha o bajar a una segunda línea cuando no caben. | `x-lnx-ui::resource-card` |
| Cabecera de página | El título va en `x-lnx-ui::page-header` como primer bloque del contenido. Subtítulo, badge de estado (slot `meta`) y acciones (slot `actions`) son opcionales. El breadcrumb no va aquí: se pasa al layout (`->layout('layouts.app', ['breadcrumbs' => [...]])`) y se pinta a la izquierda del toolbar. | `x-lnx-ui::page-header` |
| Pie de tarjeta con metadatos | Separador `border-t` con `mt-auto` para anclar el pie al fondo de la tarjeta; etiquetas a la izquierda (`tags`) y metadatos y acciones a la derecha (`meta`, `actions`). | `x-lnx-ui::resource-card` |
| Fila de mensaje en una lista | Avatar `flux:avatar` circular + contenido (remitente, asunto, vista previa) y una columna derecha con la hora arriba y la acción abajo (`self-stretch` + `justify-between`). Estado seleccionado: `bg-accent/5 ring-1 ring-inset ring-accent/25`. | `x-lnx-ui::mail-list-item` |
| Lista de filas contiguas | Contenedor `flux:card p-0` con `divide-y`; cada fila conserva su propio padding y hover, sin separadores manuales. | `x-lnx-ui::mail-list-item` |
| Piezas de un panel de detalle | `flux:card` en columna flex: cabecera `border-b`, secciones internas apiladas con `space-y-4` y pie `border-t` con `mt-4`; las acciones van en el slot `actions`. | `x-lnx-ui::mail-viewer` |
| Tarjeta de archivo o adjunto | `rounded-lg border` con chip de icono `size-9` (tono por color) y nombre truncado sobre el tamaño en texto menor. | `x-lnx-ui::attachment-card` |
| Lista de presencia | Avatar circular con punto de estado superpuesto (`size-2.5 rounded-full bg-emerald-500 ring-2`) y nombre truncado sobre la línea de contexto. | `x-lnx-ui::presence-list` |

## Superficie, color e interacción

| Uso | Base existente | Fuente |
| --- | --- | --- |
| Tarjetas | `flux:card` antes de crear una superficie propia. | `resources/views/livewire/dashboard.blade.php` |
| Acento | Variables `--color-accent`, `--color-accent-content` y `--color-accent-foreground`, con seis variantes y modo oscuro. | `resources/css/app.css` |
| Foco y controles | Mantener foco visible y verificar teclado, claro/oscuro y acentos. | `README.md`, sección «Contrato visual» |
| Metadato y acciones de un panel | `updated-label` con icono (`updated-icon`) y `flux:tooltip`, texto accesible y foco visible; el slot `actions` alinea botones a la derecha de la cabecera. Omitir el metadato si no hay fecha. | `x-lnx-ui::statistics-panel` |
| Tendencia decorativa | Mostrar la variación como texto y ocultar el SVG a lectores de pantalla; colorear la línea con clases claras y oscuras existentes. | `x-lnx-ui::trend-card` |
| Gráfico decorativo de una métrica | El título y el valor llevan el significado y el gráfico va `aria-hidden="true"`; el color sale de `color`. Línea suavizada con área degradada (`chart="line"`) o barras proporcionales al máximo con `labels` opcionales (`chart="bar"`). | `x-lnx-ui::chart-card` |
| Variación de una métrica | Texto o pastilla según `change-style` y tono `emerald`/`rose` deducido del signo (o de `trend`); mismas clases aprobadas en `x-lnx-ui::stat`. | `x-lnx-ui::metric-card` |
| Punto de color de una etiqueta | `size-2 shrink-0 rounded-full` con la clase de color resuelta por `match` (nunca ensamblada dinámicamente). | `resources/views/livewire/mail-ui/preview.blade.php` |
| Acción de destacar | Estrella en `amber-500` cuando está activa y `zinc-300`/`zinc-600` cuando no, con `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent`. | `x-lnx-ui::mail-list-item` |

## Cómo ampliar esta referencia

Después de aprobar un componente, agrega solo patrones que se repetirán. Para cada patrón nuevo, anota su uso, la clase o token existente y un componente aprobado que lo ejemplifique. Si el patrón requiere un token nuevo, actualiza primero `resources/css/app.css` y registra aquí cuándo usarlo. No conviertas una preferencia propia de una sola captura en estándar global.
