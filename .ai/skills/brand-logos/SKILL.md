---
name: brand-logos
description: Añade logos de marcas (tecnologías, proveedores, servicios) a lnx usando SVGL como fuente, vendorizando el SVG en el repositorio y exponiéndolo como componente Blade reusable. Úsala al incorporar el logo de una marca a una pantalla, tarjeta, lista de integraciones o selector de proveedor.
---

# Logos de marcas (SVGL)

lnx obtiene los logos de marcas de [SVGL](https://svgl.app), una biblioteca open source (MIT) con una API REST pública, gratuita y sin autenticación. Los SVG se consultan durante el desarrollo y se **vendorizan** en el repositorio: la aplicación **nunca** llama a SVGL en runtime.

La vía autorizada es la API REST pública más el vendorizado local. No se añade el MCP comunitario `svgl-mcp` (sus herramientas de búsqueda están detrás de pago) ni el registry de shadcn/ui (genera componentes React, ajenos a Blade/Livewire).

## Cuándo usar

- Al añadir el logo de una tecnología, proveedor o servicio (Stripe, GitHub, Google, Vercel…).
- Al construir una lista de integraciones, un selector de proveedor o una tarjeta de conexión.
- Al representar una marca en marketing o en un directorio de partners.

## Cuándo NO usar

- Iconografía de interfaz genérica: usa Flux/Heroicons, no SVGL. Aquí solo logos de marca.
- Si el logo no está en SVGL: descárgalo de la web oficial de la marca respetando su guía de marca y vendorízalo igual.
- Para consultar la API desde la aplicación en producción: el logo se descarga una vez y queda en el repo.

## Reglas de oro

1. **Vendorizar, no enlazar**: nunca uses `api.svgl.app` ni `svgl.app` como `src` en runtime.
2. **Licencia y uso**: SVGL es MIT, pero cada marca tiene sus propias normas. Antes de incorporar, revisa el campo `brandUrl`, confirma que el uso está permitido y anota la fuente.
3. **Sin `width`/`height` fijos**: conserva el `viewBox`, elimina el tamaño y deja que Tailwind (`class="size-6"`) lo controle.
4. **Accesibilidad**: un logo decorativo lleva `aria-hidden="true"`; si es la única representación de algo, aporta `aria-label` o texto adyacente.
5. **Tema claro/oscuro**: usa la variante light/dark de SVGL o `currentColor` cuando el logo sea monocromo.

## Procedimiento

### 1. Buscar el logo

```bash
curl -s "https://api.svgl.app?search=github" | jq
```

La respuesta trae `title`, `category`, `route`, `wordmark`, `url` y `brandUrl`. `route` es una URL o un objeto `{light, dark}`.

### 2. Elegir la variante

- `route` como texto → un único SVG.
- `route` como `{light, dark}` → descarga ambas; los nombres suelen ser `{slug}_light.svg` y `{slug}_dark.svg`.
- `wordmark` → versión con el nombre, útil en cabeceras anchas.
- `brandUrl` → guía de marca; revísala antes de usar.

### 3. Descargar el SVG

Usa la URL de `route` (recomendado) o el endpoint optimizado:

```bash
curl -s "https://api.svgl.app/svg/stripe.svg"
```

El endpoint `/svg/{archivo}.svg` solo existe si el logo tiene un único archivo; para variantes light/dark usa directamente las URLs de `route`.

### 4. Vendorizar y normalizar

Guarda el resultado como componente Blade anónimo, un archivo por marca:

```
resources/views/components/brand-logos/{slug}.blade.php
```

El `<svg>` raíz debe incluir `{{ $attributes }}`, conservar `viewBox` y `xmlns`, y no declarar `width`/`height`:

```blade
<svg {{ $attributes }} viewBox="0 0 256 222" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <!-- paths provenientes de SVGL -->
</svg>
```

Si la marca trae variantes, incluye ambos `<svg>` en el mismo archivo y altérnalos con `dark:hidden` / `hidden dark:block`. Si el SVG usa `fill="currentColor"`, consérvalo para que herede el color del texto. Revisa que no queden `id` capaces de colisionar entre instancias: renómbralos o elimínalos si no se referencian.

### 5. Exponerlo como componente reusable

Crea (solo la primera vez) el envoltorio `x-brand-logo`, que resuelve el parcial por nombre:

```
resources/views/components/brand-logo.blade.php
```

```blade
@props(['name'])

<x-dynamic-component :component="'brand-logos.'.$name" {{ $attributes }} />
```

Uso, con `name` en kebab-case:

```blade
<x-brand-logo name="github" class="size-6" />
<x-brand-logo name="stripe" class="size-8 text-zinc-900 dark:text-white" />
```

El consumidor asigna tamaño y color; el componente no fija dimensiones ni añade lógica de negocio.

### 6. Verificar

- El logo se ve en claro y oscuro y no desborda en móvil.
- `aria-hidden` en logos decorativos; texto alternativo o adyacente cuando aporte significado.
- Ejecuta `php artisan test --compact` si tocaste Blade con lógica y `python3 scripts/check-agent-context.py` si cambió la estructura del kit.
- Deja la fuente (`url` y `brandUrl`) anotada en el PR.

## Referencia rápida de la API

| Operación | URL |
| --- | --- |
| Todos los SVG | `https://api.svgl.app` |
| Con límite | `https://api.svgl.app?limit=10` |
| Búsqueda | `https://api.svgl.app?search=ax` |
| Por categoría | `https://api.svgl.app/category/software` |
| Categorías | `https://api.svgl.app/categories` |
| Código SVG | `https://api.svgl.app/svg/adobe.svg` |
| SVG sin optimizar | `https://api.svgl.app/svg/adobe.svg?no-optimize` |
