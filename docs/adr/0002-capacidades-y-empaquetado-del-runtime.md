# ADR 0002 — Capacidades declaradas una vez y empaquetado del runtime

- **Estado:** aceptado (2026-11-08).
- **Fecha:** 2026-11-08.
- **Ámbito:** kit `lnx`, runtime de Sam, paquete `luinuxscl/sam`, `lnx-ui`, módulos (empezando por `crm`) y apps derivadas (`crm-app`).
- **Complementa:** [ADR 0001](0001-sam-agente-de-plataforma.md). No lo sustituye: mantiene el reparto runtime (MIT) / identidad (AGPL) y decide cómo se declara una capacidad y dónde vive el código.

## Contexto

Hoy la misma operación de dominio se declara tres veces, una por superficie:

1. **Sam.** `SamProposeTool` con `RISK`, `MIN_MODE` y `authorize()`, más el `SamProposalPresenter` del módulo para pintar y confirmar el borrador.
2. **MCP.** `DraftWriteTool` con `RISK`, el atributo `WritesData` (`resource`, `operation`, `permission`) y `McpToolRegistry`.
3. **API HTTP.** Los endpoints de escritura del módulo: `Idempotency-Key`, borrador y `POST /write-drafts/{draft}/confirm`, con su propia autorización.

Las tres convergen en la misma tabla (`crm_write_drafts`) y en la misma acción de dominio, pero **no comparten la declaración**: permiso, riesgo, modo mínimo y necesidad de frase se escriben por separado. Eso ya produjo dos asimetrías reales:

- La frase se relaja para borradores reversibles **solo** en el canal Sam; MCP y la API la exigen siempre. Es una decisión correcta, pero está implementada como excepción de canal, no como regla derivada del riesgo.
- Un módulo puede declarar `RISK` en su herramienta de Sam y no declararlo en su `DraftWriteTool`, o declarar permisos distintos. Nada falla hasta que alguien lo nota en producción.

El plan de producto pide que **una capacidad declarada una vez se exponga a dos superficies con la misma autorización y auditoría**. Además persiste el riesgo que dejó abierto el ADR 0001: `App\Ai\Sam` (36 archivos) está copiado byte a byte en `lnx` y `crm-app`, y el kit es un andamio, así que cada app derivada arrastra una copia divergente del runtime.

## Decisión

### 1. `SamCapability`: una capacidad, una declaración

El runtime gana un DTO `SamCapability` que describe una operación del módulo, no una superficie:

```php
final class SamCapability
{
    public function __construct(
        public readonly string $key,          // 'crm.archive_contact', prefijo = source
        public readonly string $source,       // 'crm'
        public readonly string $label,        // texto para el catálogo y la auditoría
        public readonly SamCapabilityKind $kind,   // read | propose
        public readonly ?string $permission,  // 'crm.manage_contact'
        public readonly ?SamRisk $risk,       // null en lecturas
        public readonly SamMode $minMode,     // draft en propuestas
        public readonly bool $requiresPhrase, // derivado del riesgo, salvo undo real
        public readonly bool $undoable,       // solo si hay undo de verdad
        public readonly string $auditAction,  // read | propose | confirm | undo
        public readonly array $surfaces,      // ['sam', 'mcp', 'api', 'ui']
        public readonly array $handlers,      // herramientas que la implementan
    ) {}
}
```

- `key` es global y lleva el prefijo del `source`; el registro rechaza duplicados y prefijos ajenos con `InvalidArgumentException`, como ya hace `SamRegistry`.
- `risk` y `requiresPhrase` son coherentes por construcción: irreversible y bulk siempre piden frase; reversible solo puede relajarla declarando `undoable = true`. Un `risk` reversible sin undo es un error detectable.
- `surfaces` es informativo y se valida contra las superficies instaladas: declarar `mcp` sin herramienta MCP registrada falla en `sam:doctor`, no en el turno.

Un módulo declara su manifiesto implementando `SamCapabilityProvider` (`capabilities(): array`). Durante la migración el contrato es **opcional**: un contribuidor sin manifiesto sigue funcionando y `sam:doctor` lo reporta como pendiente, de modo que las suites de los módulos no se rompen por el orden de despliegue. Cuando todos los módulos publicados declaren, el manifiesto pasa a obligatorio y un módulo que expone herramientas sin capacidad no las publica: el runtime audita `tool_rejected` con `reason: capability_missing`.

### 2. La matriz capacidad × superficie

| Capa | Quién autoriza | Dónde se comprueba | Frase | Auditoría |
| --- | --- | --- | --- | --- |
| Sam (panel) | `SamRuntime` + `authorize()` de la herramienta | Al listar herramientas y otra vez al invocar, más alcance por registro | Solo si el riesgo la exige; reversible con undo se confirma con clic | `mcp_tool_invocations` con `channel = sam` |
| MCP | Token + permiso declarado | `WritesData::permission` al descubrir y al ejecutar | Siempre | `mcp_tool_invocations` + `mcp_write_operations` |
| API HTTP | Sesión + policy del recurso | Al crear el borrador y al confirmar | Siempre | `mcp_tool_invocations` + `crm_write_drafts` |
| UI del módulo | Sesión + policy | En la acción y en la pantalla | Según el riesgo, dentro del módulo | Evento de dominio + `crm_write_drafts` |

Reglas de la matriz:

- **La autorización nunca se delega a la superficie.** Cada capa vuelve a comprobar permiso y alcance; una superficie no puede aprobar lo que otra denegaría.
- **La confirmación solo se endurece.** Una superficie puede exigir la frase cuando la capacidad no la pide; nunca puede omitirla cuando la pide. Por eso MCP y API la exigen siempre y Sam relaja únicamente el caso reversible con undo.
- **El modo es del actor, no de la superficie.** `minMode` se compara con el modo efectivo del actor (despliegue ∩ rol ∩ preferencia) también en Sam; MCP y API no tienen modo y aplican su propia puerta.
- **El alcance es por registro.** La capacidad declara permiso; la comprobación de pertenencia sigue siendo del módulo (`accessibleTo()`, policies).

### 3. Reglas de migración de las declaraciones existentes

- **`WritesData`** conserva `resource`, `operation`, `permission` e `idempotent`. Cuando la herramienta declara una capacidad, `resource`/`operation`/`permission` deben coincidir con ella; `mcp:verify-writes` falla si divergen. El atributo no desaparece: sigue siendo lo que lee el descubrimiento de MCP.
- **`DraftWriteTool::RISK`** pasa a ser una lectura de la capacidad (`CAPABILITY::risk`). Durante una versión menor se aceptan las dos formas; si discrepan, gana la capacidad y `sam:doctor` lo reporta como drift.
- **`SamProposeTool::RISK` y `MIN_MODE`** se derivan igual. Un módulo que no declare capacidad sigue funcionando como hoy, con una advertencia en `sam:doctor`.
- **`McpToolRegistry`** no cambia su función (registrar clases de herramienta); el catálogo de capacidades se lee del `SamRegistry`, que es donde el módulo declara.
- **Acciones de UI** (navegar, abrir un filtro) no son capacidades: siguen siendo `SamAction` y no pasan por esta matriz porque no escriben ni leen por fuera del alcance.

### 4. Empaquetado del runtime: `luinuxscl/lnx-sam` (MIT)

`App\Ai\Sam` se extrae a un paquete **MIT** con namespace `Luinuxscl\LnxSam`:

- Lo requieren `lnx` (MIT), `crm-app` (AGPL-3.0-or-later) y `luinuxscl/sam` (AGPL-3.0-or-later). Un paquete AGPL no puede ser dependencia del kit MIT, así que **no** se mete en `sam`.
- El paquete contiene el runtime, el contrato, los DTO, la auditoría, las cuotas y la puerta de confirmación. **No** contiene identidad, marca, panel, ni conocimiento de dominio.
- `lnx` deja de llevar `app/Ai/Sam` en el andamio y pasa a requerir el paquete; `crm-app` retira su copia y actualiza el lock.
- Durante una versión menor el paquete registra alias `App\Ai\Sam\*` para no romper imports de módulos ya publicados; el alias se retira en la siguiente mayor.
- El comando de sincronización del andamio (`scripts/update-agent-kit.py`) deja de portar `app/Ai/Sam` porque ya no existe en el kit.

### 5. Introspección de solo lectura

El paquete expone cuatro comandos que no escriben ni llaman al proveedor:

| Comando | Qué responde |
| --- | --- |
| `sam:capabilities` | Catálogo por módulo: clave, tipo, permiso, riesgo, modo mínimo, superficies |
| `sam:describe <clave>` | El detalle de una capacidad y qué herramientas la implementan |
| `sam:run <clave> --dry-run` | Valida permiso y alcance del actor indicado y muestra el borrador que se propondría, sin crearlo |
| `sam:doctor` | Drift: capacidades sin herramienta, herramientas sin capacidad, `WritesData` que no coincide, riesgo reversible sin undo |

Todos devuelven código de salida distinto de cero cuando encuentran un problema, para poder encadenarlos en CI.

## Consecuencias

- **Una sola fuente de verdad por capacidad.** El riesgo, el permiso y el modo mínimo se declaran una vez; las tres superficies los leen. La asimetría de la frase deja de ser una excepción de canal y pasa a ser una regla derivada del riesgo.
- **El catálogo es inspeccionable sin leer código.** `sam:capabilities` y `sam:doctor` permiten auditar qué expone cada módulo antes de un despliegue.
- **La extracción elimina la copia divergente** que el ADR 0001 dejó como riesgo y que ya obligó a arreglar dos veces el mismo defecto de tipos en `lnx` y `crm-app`.
- **Cambio incompatible de distribución:** las apps derivadas dejan de tener `app/Ai/Sam` y pasan a requerir el paquete. Necesita una versión mayor del kit o, como mínimo, una nota de actualización explícita y los alias de compatibilidad.
- **La licencia se mantiene:** el kit y el runtime son MIT; el producto (identidad, panel, dominio) sigue AGPL. La frase pública no cambia.
- **Riesgo de fuga de dominio:** el paquete nuevo no debe conocer `crm`, ni rutas, ni tablas. Cualquier intento de meter conocimiento de negocio ahí se rechaza en revisión.
- **Riesgo de doble declaración transitoria:** durante la migración conviven `RISK` y la capacidad. `sam:doctor` es lo que evita que la convivencia se vuelva permanente.

## Alternativas consideradas

- **Mantener tres declaraciones y sincronizarlas a mano.** Descartada: es el estado actual y ya divergió.
- **Poner las capacidades en `luinuxscl/sam`.** Descartada: el kit MIT no puede depender de un paquete AGPL.
- **Un registro de capacidades por superficie.** Descartada: reproduce el problema con otro nombre.
- **Guardar las capacidades en base de datos.** Descartada: una capacidad es una declaración de código, no dato de ejecución; la auditoría ya vive en `mcp_tool_invocations`.
- **Seguir copiando el runtime por app.** Descartada por el ADR 0001 y por la evidencia: el mismo defecto se corrigió dos veces.
- **Extraer el runtime a un paquete AGPL junto con `sam`.** Descartada: contamina la base MIT y obliga a las apps derivadas a aceptar AGPL en el kit.

## Migración, en orden

1. `lnx`: `SamCapability`, validación en `SamRegistry`, catálogo y los cuatro comandos de solo lectura, con pruebas. — **implementado (2026-11-08)**, portado a `crm-app`.
2. `crm`: `capabilities()` en `CrmSamContributor` para sus lecturas y sus ocho propuestas; `DraftWriteTool` y `SamProposeTool` leen de ahí. **Sin cambio de comportamiento**: la suite del módulo debe seguir verde sin tocar expectativas.
3. Crear `luinuxscl/lnx-sam` (MIT), mover el runtime con sus pruebas y publicar la primera versión.
4. `lnx`, `crm-app` y `sam` requieren el paquete; `crm-app` retira la copia y actualiza el lock. Alias de compatibilidad activos.
5. `sam` deja de importar `App\Ai\Sam\*`; el paquete pasa a ser su única dependencia de runtime.
6. `CONTRATO-MODULO.md` §3.9 pasa a normativo con el manifiesto, la matriz y los comandos.

## Cuestiones abiertas

- Nombre definitivo y namespace del paquete (`lnx-sam` / `Luinuxscl\LnxSam` son la propuesta).
- Si las lecturas se declaran como capacidad (`kind: read`) o solo las propuestas. La propuesta es declararlas: el catálogo debe responder qué puede leer Sam sin abrir el código.
- Si el catálogo alimenta también la navegación o el panel de permisos.
- Cuánto dura la ventana de alias `App\Ai\Sam\*` antes de la versión mayor.

## Aprobación

Aceptado por el mantenedor el 2026-11-08:

- [x] Una capacidad se declara una vez y la leen todas las superficies.
- [x] La confirmación solo se endurece; Sam relaja únicamente el reversible con undo.
- [x] El runtime se extrae a un paquete MIT y el producto sigue AGPL.
- [x] La introspección es de solo lectura y sirve para CI.
