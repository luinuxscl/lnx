# Fundamentos visuales para componentes de lnx

Esta referencia registra decisiones reutilizables al aprobar componentes de la biblioteca. `resources/css/app.css` define los tokens que ejecuta la aplicación; este archivo describe cómo usarlos. Comprueba ambos antes de crear una variante nueva.

## Tipografía y títulos

| Uso | Base existente | Ejemplo |
| --- | --- | --- |
| Fuente de interfaz | `--font-sans` usa Instrument Sans y fuentes de sistema como respaldo. | `resources/css/app.css` |
| Título de página | `flux:heading` con `level="1"`; el texto visible se traduce. | `resources/views/livewire/dashboard.blade.php` |
| Título de sección | `flux:heading` con `level="2"`. | `resources/views/livewire/dashboard.blade.php` |
| Título dentro de tarjeta | `flux:heading` con `level="3"`. | `resources/views/livewire/dashboard.blade.php` |
| Título de panel de métricas | `flux:heading` con `level="2"` y `size="lg"`, para distinguirlo del valor de cada métrica sin dominar la tarjeta. | `resources/views/components/statistics-panel.blade.php` |
| Texto de apoyo | `flux:text`; usa un tamaño menor solo si la información es secundaria. | `resources/views/livewire/dashboard.blade.php` |

## Espaciado y composición

| Relación | Base existente | Ejemplo |
| --- | --- | --- |
| Controles o iconos cercanos | `gap-2`. | `resources/views/components/desktop-user-menu.blade.php` |
| Acciones y tarjetas próximas | `gap-4`. | `resources/views/livewire/dashboard.blade.php` |
| Secciones o grupos de campos | `mt-6`, `space-y-6` o `gap-6` según el contenedor. | `resources/views/livewire/dashboard.blade.php` |
| Grilla de tarjetas | El padre define columnas responsivas y separación; el componente no fija su ancho. | `resources/views/livewire/dashboard.blade.php` |
| Cabecera y métricas de un panel | `space-y-4` entre cabecera y grilla; el panel contiene una grilla de 12 columnas con `gap-4` y cada métrica recibe su `col-span-*` desde la invocación. | `resources/views/components/statistics-panel.blade.php` |
| Métricas como tarjetas individuales | Un contenedor externo usa `grid grid-cols-12 gap-4` y asigna el `col-span-*` a cada `x-stat-card`; la tarjeta reutiliza `x-stat`. | `resources/views/components/stat-card.blade.php` |
| Tarjetas de tendencia | El padre asigna `col-span-12 md:col-span-6 xl:col-span-4` para mostrar una, dos o tres tarjetas por fila según el ancho. | `resources/views/components/trend-card.blade.php` |
| Tarjetas-enlace a un área | `x-tool-card` envuelve un `flux:card` completo en un `<a>` de bloque; el contenedor asigna `col-span-12 sm:col-span-6 xl:col-span-4` (tres por fila) o `xl:col-span-3` (cuatro por fila). | `resources/views/components/tool-card.blade.php` |
| Cabecera de tarjeta con etiqueta y badges | Fila con `flex-wrap`: la etiqueta no se trunca y los badges usan `ms-auto` para alinearse a la derecha o bajar a una segunda línea cuando no caben. | `resources/views/components/resource-card.blade.php` |
| Pie de tarjeta con metadatos | Separador `border-t` con `mt-auto` para anclar el pie al fondo de la tarjeta; etiquetas a la izquierda (`tags`) y metadatos y acciones a la derecha (`meta`, `actions`). | `resources/views/components/resource-card.blade.php` |

## Superficie, color e interacción

| Uso | Base existente | Fuente |
| --- | --- | --- |
| Tarjetas | `flux:card` antes de crear una superficie propia. | `resources/views/livewire/dashboard.blade.php` |
| Acento | Variables `--color-accent`, `--color-accent-content` y `--color-accent-foreground`, con seis variantes y modo oscuro. | `resources/css/app.css` |
| Foco y controles | Mantener foco visible y verificar teclado, claro/oscuro y acentos. | `README.md`, sección «Contrato visual» |
| Metadato y acciones de un panel | `updated-label` con icono (`updated-icon`) y `flux:tooltip`, texto accesible y foco visible; el slot `actions` alinea botones a la derecha de la cabecera. Omitir el metadato si no hay fecha. | `resources/views/components/statistics-panel.blade.php` |
| Tendencia decorativa | Mostrar la variación como texto y ocultar el SVG a lectores de pantalla; colorear la línea con clases claras y oscuras existentes. | `resources/views/components/trend-card.blade.php` |

## Cómo ampliar esta referencia

Después de aprobar un componente, agrega solo patrones que se repetirán. Para cada patrón nuevo, anota su uso, la clase o token existente y un componente aprobado que lo ejemplifique. Si el patrón requiere un token nuevo, actualiza primero `resources/css/app.css` y registra aquí cuándo usarlo. No conviertas una preferencia propia de una sola captura en estándar global.
