# Centro de ayuda integrado

> **Estado:** fases 1, 15 y 16 implementadas y cerradas (2026-10-21) · **Referencia:** [#78](https://github.com/luinuxscl/lnx/issues/78)
> Centraliza en una sola vista la documentación, las preguntas frecuentes y el acceso a soporte, y define cómo cada paquete de personalización aporta sus propias entradas.

---

## 1. Objetivo

Toda la ayuda de la aplicación vive en **`/help`**, accesible solo a cuentas autenticadas. La vista reúne:

- **Guías** de producto escritas en Markdown.
- **FAQ** agrupadas por tema.
- **Soporte**, reutilizando el flujo de tickets y el panel de feedback ya existentes.

Cada paquete declara sus propias guías y FAQ sin tocar la app, siguiendo el patrón de registros ya usado por permisos, notificaciones, paquetes e identidad.

## 2. Decisiones fijadas (2026-10-20)

| Decisión | Valor |
| --- | --- |
| Audiencia | Solo usuarios autenticados; no hay ayuda pública en la fase 1 |
| Formato | Markdown renderizado en servidor |
| Edición desde la UI | Solo `root`, en `/admin/help` (etapa 15, 2026-10-21) |
| Búsqueda con IA | No en la fase 1; se evalúa en la etapa 16 |
| Fuente del contenido | El paquete que posee la funcionalidad, versionado con su código |
| Bilingüe | Sí, español por defecto e inglés, con claves de traducción |

## 3. Estado de partida

- **Soporte ya existe**: panel de feedback en el header, tickets, bandeja de root, notas internas y `App\Support\TicketService`.
- **Knowledge/RAG ya existe**, pero es una herramienta interna de root para indexar documentos; no es documentación de producto.
- **La ayuda está hardcodeada** en `App\Ai\Tools\ProductHelpSearchTool` (siete temas en un array). Esta iniciativa la reemplaza por el registro.
- **No hay FAQ ni Help Center** en la aplicación.
- `league/commonmark` ya está instalado, así que `Str::markdown()` no añade dependencias.
- El patrón de extensibilidad vigente está en `CONTRATO-MODULO.md` y en los registros de `app/Support/`.

## 4. Arquitectura de la fase 1

### 4.1 Registro

`App\Support\Help\HelpRegistry` es un singleton en memoria. Cada provider de paquete lo alimenta en su `boot()`:

```php
use App\Support\Help\HelpRegistry;

public function boot(): void
{
    app(HelpRegistry::class)->register('luz', [
        'luz.primeros-pasos' => [
            'type' => 'article',                       // article | faq
            'group' => 'luz::help.groups.getting_started',
            'title' => 'luz::help.getting_started.title',
            'summary' => 'luz::help.getting_started.summary',
            'body' => 'luz::help.getting_started.body', // Markdown traducido
            'roles' => ['admin', 'user'],
            'order' => 1,
            'route' => 'luz.dashboard',                 // opcional
        ],
        'luz.faq-accesos' => [
            'type' => 'faq',
            'group' => 'luz::help.groups.faq',
            'title' => 'luz::help.faq.access.title',
            'body' => 'luz::help.faq.access.body',
            'roles' => ['user'],
            'order' => 2,
        ],
    ]);
}
```

Reglas del registro:

- El slug **debe** llevar el prefijo del paquete (`luz.`). Un slug vacío, sin prefijo o duplicado lanza `InvalidArgumentException`.
- `type` solo admite `article` o `faq`.
- `roles` usa valores de `App\Enums\Role`. `root` ve todo lo declarado; el resto solo lo que permiten sus roles.
- `route` es opcional y debe ser una ruta nombrada existente; se usa para el enlace «Ir a la función».
- El contenido se resuelve al idioma actual en el momento de renderizar. Una clave faltante se detecta en pruebas, no en producción.

### 4.2 Contrato de lectura

- `all(User $user)`: entradas visibles, ordenadas por grupo y orden.
- `find(string $slug, User $user)`: entrada visible o `null`.
- `search(string $term, User $user)`: coincidencias en título, resumen y cuerpo ya resueltos; nunca incluye entradas ocultas.
- Markdown con `Str::markdown($body, ['html_input' => 'strip', 'allow_unsafe_links' => false])`: sin HTML crudo ni enlaces inseguros.

### 4.3 Vista

`App\Livewire\Help\Index` en `/help` (`help.index`), middleware `auth` y `verified`, entrada en la región `bottom` del menú para `root`, `admin` y `user`, icono `lifebuoy` y sección propia en breadcrumbs.

- Estado compartible en la URL: `?tab=guides|faq|support`, `?topic=<slug>` y `?search=<término>` con `#[Url]`.
- Navegación por categorías, buscador, detalle de artículo y FAQ en acordeones.
- La pestaña de soporte enlaza al panel existente; no crea un segundo flujo de tickets.
- Sin artículos visibles, la vista muestra un estado vacío útil, no un error.

### 4.4 Integración con soporte

- El dropdown del header pasa de «Support» a «Ayuda»: Centro de ayuda, Preguntas frecuentes, Contactar soporte y Mis solicitudes.
- Cada artículo ofrece «¿No resolvió tu duda?», que abre el panel de soporte con el artículo como contexto.
- El ticket registra el slug en una columna nullable `help_topic`; nunca se envía el cuerpo del artículo ni datos del formulario.
- `ProductHelpSearchTool` consulta el registro, respeta roles y devuelve como máximo tres entradas con título, resumen y enlace.
- Cada pantalla clave enlaza su guía con `<x-help-topic slug="..." />`, que no renderiza nada si el tema no existe o no es visible para la identidad. Las notificaciones declaran su tema con la clave `help` del registro de tipos y muestran «Leer la guía» solo si la entrada es legible.

### 4.5 Contenido de lnx

El contenido propio se declara desde un provider de la app con Markdown bilingüe en `lang/es/help.php` y `lang/en/help.php`, o con archivos `resources/help/{es,en}/*.md` registrados desde ese provider. Cobertura mínima en [#82](https://github.com/luinuxscl/lnx/issues/82).

### 4.6 Persistencia y ciclo editorial (fase 15)

`help:sync` materializa lo declarado en `help_groups` y `help_entries`, siguiendo el modelo de `NavigationSync`.

- **Precedencia: root > paquete > lnx.** Cada fila guarda las claves de traducción del paquete (`title_key`, `summary_key`, `body_key`), los roles y el orden. Un override de root (`overridden = true`) aporta textos es/en propios, roles, posición o visibilidad y nunca se pisa.
- **Tres estados en la UI:** **Auto** (intacta), **Editado por root** (override) y **Disponible** (declarada sin fila todavía; se puede publicar desde el editor).
- **Idempotencia.** Dos ejecuciones seguidas no duplican, no reordenan ni generan actividad nueva.
- **Ocultar, nunca borrar.** Si la declaración desaparece, la fila se desactiva (`enabled = false`); la sincronización no la resucita.
- **Auditoría.** Creaciones, ediciones, ocultamientos, reordenamientos, publicaciones y restauraciones se registran en Actividad administrativa con las acciones `help_entry_*` y `help_group_updated`.
- **Editor.** `/admin/help` (`admin.help.index`, solo `root`) lista por paquete, tipo y estado, con búsqueda y filtros; edita título, resumen y cuerpo en pestañas es/en; ajusta roles; reordena; oculta/publica; publica disponibles y restaura al valor del paquete.
- **Lectura.** `HelpCatalog` combina el registro en memoria con las filas persistidas; `Help\Index` y `ProductHelpSearchTool` lo usan, así que la ayuda funciona aunque no se haya sincronizado todavía.

### 4.7 Búsqueda semántica y citas (fase 16)

La ayuda publicada se indexa en la base de sistema `product_help`, reutilizando Knowledge:

- `App\Support\Help\HelpKnowledgeSync` crea la base (`is_system = true`) y un `KnowledgeDocument` por entrada e idioma (`es`/`en`), con título, resumen y cuerpo resueltos a texto.
- La base no aparece en la UI de Knowledge ni se puede adjuntar a un chat; su contenido solo se consulta a través de `App\Ai\Knowledge\HelpSearchService`.
- La indexación se dispara al final de `help:sync`, al publicar o restaurar y al guardar, ocultar o publicar desde `/admin/help`. Usa `IndexKnowledgeDocumentJob`; se necesita un worker de cola.
- La sincronización es idempotente y elimina los documentos de entradas ocultas o retiradas.
- La búsqueda filtra por idioma y por los slugs visibles para la identidad **antes** de puntuar, y vuelve a validar cada resultado contra `HelpCatalog` después. Si no hay embeddings o la IA está deshabilitada, usa la similitud textual del motor Knowledge.
- El chat consulta la ayuda automáticamente en cada mensaje, cita el título y la URL del artículo y persiste las citas en `ai_messages.citations`; la vista las muestra como enlaces a `/help?topic=<slug>`.

## 5. Fases y issues

| Fase | Issue | Entrega | Depende de |
| --- | --- | --- | --- |
| 14 | [#78](https://github.com/luinuxscl/lnx/issues/78) (cerrada) | `HelpRegistry`, validación, búsqueda, Markdown seguro y contrato §9 | — |
| 14 | [#75](https://github.com/luinuxscl/lnx/issues/75) (cerrada) | Vista `/help` unificada, navegación, búsqueda y enlaces profundos | #78 |
| 14 | [#77](https://github.com/luinuxscl/lnx/issues/77) (cerrada) | Header de ayuda, CTA de soporte con contexto y migración de `ProductHelpSearchTool` | #78, #75 |
| 14 | [#82](https://github.com/luinuxscl/lnx/issues/82) (cerrada) | Guías y FAQ iniciales de lnx, bilingües | #78 |
| 15 | [#81](https://github.com/luinuxscl/lnx/issues/81) (cerrada) | Persistencia, `help:sync` idempotente, precedencia de root y auditoría | #78, #75 |
| 15 | [#76](https://github.com/luinuxscl/lnx/issues/76) (cerrada) | Editor `/admin/help` para root | #81 |
| 16 | [#79](https://github.com/luinuxscl/lnx/issues/79) (cerrada) | Ayuda contextual y enlaces profundos desde componentes y notificaciones | #75, #82 |
| 16 | [#80](https://github.com/luinuxscl/lnx/issues/80) (cerrada) | Búsqueda semántica con Knowledge y citas en el chat | #75, #81 |

**Orden de ejecución:** #78 → #75 → #77 → #82 (fase 14, integrada el 2026-10-20) → #81 → #76 (fase 15, integrada el 2026-10-21) → #79 → #80 (fase 16, integrada el 2026-10-21).

Los criterios de aceptación detallados viven en cada issue de GitHub; este documento conserva el orden, las dependencias y las decisiones.

## 6. Fuera de alcance

- Ayuda pública para visitantes.
- Capturas de pantalla o video dentro de los artículos.
- Traducciones adicionales al español e inglés.
- CMS editorial completo en la fase 1.
- Reemplazar el módulo Knowledge: el centro de ayuda lo reutiliza en la fase 16.

## 7. Cómo retomar el trabajo

1. Consultar el estado real en GitHub: `gh issue list --state open` (fases 14, 15 y 16 cerradas).
2. Crear la rama desde `develop`: `git checkout develop && git pull && git checkout -b feature/help-context`.
3. Implementar en el orden de la tabla, empezando por la fase 16.
4. Para cada issue, leer `.ai/rules/index.md` y las reglas aplicables antes de editar.
5. Verificar con `php artisan test --compact`, `vendor/bin/pint --dirty --format agent` y `composer types:check`.
6. Actualizar `README.md`, `CONTRATO-MODULO.md` y este documento en el mismo PR cuando cambie el comportamiento. Tras desplegar la fase 15, ejecutar `php artisan migrate --force` y `php artisan help:sync` (o sembrar) para materializar el catálogo.
7. Integrar en `develop` y luego en `main`; cerrar cada issue solo tras comprobar sus criterios sobre la implementación integrada.

## 8. Verificación del plan

- [x] Decisiones de audiencia, formato, edición e IA fijadas.
- [x] Issues de las tres etapas creadas en GitHub con dependencias corregidas.
- [x] Roadmap actualizado con las etapas 14 a 16.
- [x] Implementación de la fase 1 (issues #78, #75, #77 y #82), integrada en `main` y cerrada el 2026-10-20.
- [x] Implementación de la fase 15 (issues #81 y #76): persistencia, `help:sync`, precedencia de root, auditoría y editor `/admin/help`, integrada y cerrada el 2026-10-21.
- [x] Implementación de la fase 16 (issues #79 y #80): ayuda contextual, búsqueda semántica en `product_help`, filtrado por idioma/roles y citas enlazadas en el chat, integrada y cerrada el 2026-10-21.

## 9. Mantenimiento

Actualizar este documento cuando se abra o cierre una fase, cambie una dependencia, se altere el alcance o se adopte una decisión nueva. GitHub sigue siendo la fuente del estado y de los criterios de aceptación de cada issue.

