# Fundamentos visuales para componentes de lnx

Esta referencia registra decisiones reutilizables al aprobar componentes de la biblioteca. `resources/css/app.css` define los tokens que ejecuta la aplicación; este archivo describe cómo usarlos. Comprueba ambos antes de crear una variante nueva.

## Tipografía y títulos

| Uso | Base existente | Ejemplo |
| --- | --- | --- |
| Fuente de interfaz | `--font-sans` usa Instrument Sans y fuentes de sistema como respaldo. | `resources/css/app.css` |
| Título de página | `flux:heading` con `level="1"`; el texto visible se traduce. | `resources/views/livewire/dashboard.blade.php` |
| Título de sección | `flux:heading` con `level="2"`. | `resources/views/livewire/dashboard.blade.php` |
| Título dentro de tarjeta | `flux:heading` con `level="3"`. | `resources/views/livewire/dashboard.blade.php` |
| Texto de apoyo | `flux:text`; usa un tamaño menor solo si la información es secundaria. | `resources/views/livewire/dashboard.blade.php` |

## Espaciado y composición

| Relación | Base existente | Ejemplo |
| --- | --- | --- |
| Controles o iconos cercanos | `gap-2`. | `resources/views/components/desktop-user-menu.blade.php` |
| Acciones y tarjetas próximas | `gap-4`. | `resources/views/livewire/dashboard.blade.php` |
| Secciones o grupos de campos | `mt-6`, `space-y-6` o `gap-6` según el contenedor. | `resources/views/livewire/dashboard.blade.php` |
| Grilla de tarjetas | El padre define columnas responsivas y separación; el componente no fija su ancho. | `resources/views/livewire/dashboard.blade.php` |

## Superficie, color e interacción

| Uso | Base existente | Fuente |
| --- | --- | --- |
| Tarjetas | `flux:card` antes de crear una superficie propia. | `resources/views/livewire/dashboard.blade.php` |
| Acento | Variables `--color-accent`, `--color-accent-content` y `--color-accent-foreground`, con seis variantes y modo oscuro. | `resources/css/app.css` |
| Foco y controles | Mantener foco visible y verificar teclado, claro/oscuro y acentos. | `README.md`, sección «Contrato visual» |

## Cómo ampliar esta referencia

Después de aprobar un componente, agrega solo patrones que se repetirán. Para cada patrón nuevo, anota su uso, la clase o token existente y un componente aprobado que lo ejemplifique. Si el patrón requiere un token nuevo, actualiza primero `resources/css/app.css` y registra aquí cuándo usarlo. No conviertas una preferencia propia de una sola captura en estándar global.
