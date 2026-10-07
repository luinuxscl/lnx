---
name: create-ui-component
description: Crea y mejora componentes Blade o Livewire reutilizables para lnx a partir de capturas o referencias visuales. Úsala al adaptar un componente de otra web, aprobarlo para la biblioteca o consolidar estilos de componentes; para diseñar una pantalla completa usa design-screen.
---

# Crear un componente de interfaz

Convierte una referencia visual en un componente propio de lnx. La captura y el propósito bastan para comenzar el análisis; no exijas una especificación completa al usuario.

## Preparación

1. Lee la sección «Trabajo con agentes» y el contrato visual de `README.md`, `.ai/rules/index.md` y las reglas de las rutas que vayas a cambiar. Inspecciona `resources/css/app.css`, los [fundamentos de estilo](references/style-foundations.md), un componente similar, la pantalla de destino y la galería del paquete `lnx-ui::design-gallery` (`vendor/luinuxscl/lnx-ui/resources/views/design-gallery.blade.php` o su clon en `.ai/rules/local.md`).
2. Aplica la skill instalada `tailwind-design-system` para tokens, composición, responsividad y accesibilidad en Tailwind CSS 4. Usa las skills de Flux, Livewire y `design-screen` cuando correspondan. Las recomendaciones genéricas de otra skill se adaptan a las decisiones de lnx: Flux, temas existentes y textos es/en.
3. Busca primero componentes Flux y Blade existentes. Identifica en la captura estructura, jerarquía, datos, acciones y estados; distingue lo visible de lo que no se puede inferir. Adapta colores, tipografía, iconos y espaciado al tema de la aplicación.

## Ficha previa

Presenta una ficha breve **antes de escribir el componente**. Completa lo inferible de la captura y pregunta solo por decisiones materiales pendientes:

| Campo | Qué proponer |
| --- | --- |
| Propósito y destino | Qué resuelve; primera pantalla o package que lo consumirá. |
| Estructura visual | Partes de la referencia que conservarás y adaptación al diseño de lnx. |
| Datos y API | Props, slots, etiquetas, formatos y procedencia de los datos. |
| Acciones | Enlaces, eventos, validación o ausencia de interacción. |
| Estados | Variantes necesarias; vacío, carga, error y deshabilitado solo cuando apliquen. |
| Tipo | Blade de presentación, Blade con Alpine para interacción local o Livewire por estado/acciones del servidor. |
| Ancho | Fracción de 12 columnas en móvil, pantalla mediana y escritorio. |

Para el ancho ofrece `12/12`, `6/12`, `4/12` y `3/12`, explicando cuántas piezas caben por fila. Muestra una combinación recomendada y las alternativas relevantes para la captura; no impongas una única distribución. Para tarjetas de estadísticas comienza por `12/12` en móvil, `6/12` en pantalla mediana y `3/12` en escritorio. **La grilla contenedora asigna el ancho**; no añadas una prop de ancho al componente. Un ejemplo de uso es `grid grid-cols-12 gap-4` en el padre y `col-span-12 md:col-span-6 xl:col-span-3` en cada elemento.

Espera la respuesta del usuario a las decisiones pendientes antes de implementar. Si la solicitud ya las resolvió, muestra la ficha y continúa con lo autorizado.

## Construcción y revisión

- Crea primero una pieza Blade con props y slots cuando solo presenta contenido. Usa Alpine para interacción local que no necesita servidor; reserva Livewire para estado, validación o acciones del servidor. Deja las consultas, reglas de acceso y datos de negocio en el consumidor o en la capa que corresponda. Mantén una API pequeña y explícita.
- Si la referencia repite una pieza visual, considera extraerla como componente reutilizable y componer derivados para sus usos distintos (por ejemplo, una métrica, una tarjeta individual y un panel de métricas). Reutiliza la misma pieza y su API donde encaje, sin duplicar su presentación ni forzar todos los usos al mismo contenedor.
- Crea el componente en el paquete compartido `luinuxscl/lnx-ui` (su carpeta de desarrollo en vivo está en `.ai/rules/local.md`) usando el namespace `x-lnx-ui::`; solo crea en `resources/views/components/` de esta app los componentes muy específicos de su dominio o acoplados al host (modelos, rutas o servicios). No dupliques en la app lo que ya vive en el paquete. Mantén una API pequeña y explícita.
- Reutiliza Flux y los tokens de `resources/css/app.css`. Mantén las clases de Tailwind detectables por el build; evita ensamblar nombres de utilidades dinámicamente. Respeta los temas claro/oscuro, las variantes de acento, foco visible, semántica y navegación por teclado. Traduce el texto visible según las reglas del proyecto.
- Prepara una vista previa local del candidato, sus variantes y estados pertinentes, junto a un ejemplo copiable de invocación con datos de muestra. Si también se integra en una pantalla real, usa la misma API allí.
- Verifica la presentación en español e inglés, móvil y escritorio, modos claro/oscuro y acentos disponibles. Comprueba la interacción por teclado. Ejecuta el build de frontend; cuando exista comportamiento, añade y ejecuta pruebas enfocadas. Muestra el resultado concreto al usuario para que decida si lo aprueba para la biblioteca.

## Aprobación y aprendizaje

1. Tras aprobarse el componente para la biblioteca, deja su ejemplo definitivo en la vista de la galería del paquete `lnx-ui::design-gallery`, dentro de la sección de su tipo y con un nombre y ancla estable (`#<nombre>`) para poder referenciarlo con la IA; documenta el patrón y comunica el nombre, el ancla, la invocación y las verificaciones. Si aún no se aprueba, conserva el candidato y aplica los cambios solicitados antes de catalogarlo.
2. Actualiza los [fundamentos de estilo](references/style-foundations.md) solo con decisiones visuales aprobadas que sirvan a otros componentes: espaciado, tipografía, títulos, superficies, foco o distribución. Indica el componente que las demuestra. Si hace falta un token real de CSS, cámbialo en `resources/css/app.css`, que sigue siendo la fuente de verdad; evita duplicar valores CSS en la referencia.
3. Revisa brevemente qué parte del procedimiento causó dudas, retrabajo o una mejora repetible. Si hay un aprendizaje generalizable, ofrece abrir una mejora de esta skill con el problema observado y la corrección propuesta. Si el usuario acepta, modifica solo lo necesario y comprueba el procedimiento con el caso que reveló el problema y otro caso representativo; después valida la skill y sincronízala con Boost. Si el caso fue específico de ese componente, no amplíes la skill. Esta revisión opcional no bloquea la incorporación del componente aprobado.
