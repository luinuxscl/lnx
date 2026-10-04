---
name: create-ui-component
description: Crea componentes Blade o Livewire reutilizables para lnx a partir de capturas o referencias visuales. Úsala cuando el usuario quiera adaptar un componente visto en otra web, añadirlo al catálogo visual o estandarizar un patrón de interfaz; para diseñar una pantalla completa usa design-screen.
---

# Crear un componente de interfaz

Convierte una referencia visual en un componente propio de lnx. La captura y el propósito bastan para comenzar el análisis; no exijas una especificación completa al usuario.

## Preparación

1. Lee la sección «Trabajo con agentes» y el contrato visual de `README.md`, `.ai/rules/index.md` y las reglas de las rutas que vayas a cambiar. Inspecciona `resources/css/app.css`, un componente similar, la pantalla de destino y `resources/views/design-gallery.blade.php`.
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

## Construcción y catálogo

- Crea primero una pieza Blade con props y slots cuando solo presenta contenido. Usa Alpine para interacción local que no necesita servidor; reserva Livewire para estado, validación o acciones del servidor. Deja las consultas, reglas de acceso y datos de negocio en el consumidor o en la capa que corresponda. Mantén una API pequeña y explícita.
- Guarda los componentes en la estructura existente de Blade o Livewire de esta app. Los packages diseñados para lnx pueden invocarlos desde la aplicación anfitriona. Considera extraer un package independiente solo cuando el patrón y su API sean estables y el usuario lo solicite.
- Reutiliza Flux y los tokens de `resources/css/app.css`. Mantén las clases de Tailwind detectables por el build; evita ensamblar nombres de utilidades dinámicamente. Respeta los temas claro/oscuro, las variantes de acento, foco visible, semántica y navegación por teclado. Traduce el texto visible según las reglas del proyecto.
- Añade a la galería local una vista previa del componente, sus variantes y los estados pertinentes, junto a un ejemplo copiable de invocación con datos de muestra. Si también se integra en una pantalla real, usa la misma API allí.
- Verifica la presentación en español e inglés, móvil y escritorio, modos claro/oscuro y acentos disponibles. Comprueba la interacción por teclado. Ejecuta el build de frontend; cuando exista comportamiento, añade y ejecuta pruebas enfocadas. Documenta el patrón nuevo en `README.md` y comunica la ruta, la invocación y las comprobaciones realizadas.
