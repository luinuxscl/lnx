# Registro de cambios de lnx

Todas las releases notables del kit **lnx** se documentan en este archivo. El detalle de los cambios del kit para agentes (`.ai/`) vive en [`.ai/CHANGELOG.md`](.ai/CHANGELOG.md).

El kit sigue [SemVer](https://semver.org/lang/es/) y cada release se publica con una etiqueta anotada `vX.Y.Z` sobre `main`. La versión vigente se declara en `.ai/kit-manifest.json` como `lnx_version` y se copia a cada proyecto creado con `scripts/new-project.sh`.

## [No publicado]

### Añadido

- **Sam: cola de aprobaciones y descarte de borradores.** `SamProposalPresenter` gana `pendingFor(User, int $limit = 5)` y `SamRuntime::pendingProposals()` agrega los borradores pendientes de todos los módulos para la sección permanente del dashboard. `SamProposal` añade `reviewUrl`, `canReject` y `rejectUrl`: el módulo dueño del borrador declara la acción terminal y la superficie solo publica un formulario hacia esa URL. `WidgetItem` gana `prompt` para que un atajo abra el panel con la petición puesta.
- **Dashboard: contrato de widgets y host genérico (#93).** `App\Support\Dashboard\*`: `DashboardRegistry`, `DashboardWidget`, `Widget`, `DashboardSection`, `WidgetType` y DTOs tipados (`StatData`, `StatsData`, `SeriesData`, `ListData`, `ApprovalsData`, `ActionData`, `TimelineData`, `TextData`, `WidgetItem`, `ApprovalItem`). El dashboard deja de tener datos cableados: el host recoge los widgets registrados por los paquetes, comprueba `visible()` antes de `resolve()` y pinta secciones fijas `Sam`/`Stats`/`Access`. El kit registra `lnx.users`, `lnx.root_tools` y `lnx.account`; la presentación vive en `x-lnx-ui::widget` (lnx-ui v1.27.0). El contrato se documenta en `CONTRATO-MODULO.md` §3.10.
- **Sam: contexto de fila y señales deterministas.** `SamContributor` gana `rowContext(User, resource, id, filters)` —el navegador solo nombra el recurso y el id; el módulo resuelve etiqueta, filtros y permiso, y responde `null` fuera de alcance— y `signals(User, SamContext)`, que devuelve `SamSignal` (`name`, `value`, `evidence`). `SamContext` transporta `signals` y `SamRuntime::contextForRow()` los adjunta también al contexto de ruta; el prompt avisa de que están calculadas y no se inventan. `SamSignal` nuevo en `App\Ai\Sam`.
- **Sam: saldo del proveedor con mensaje propio.** `SamRuntime` mapea `Laravel\Ai\Exceptions\InsufficientCreditsException` a `credits` en lugar de `failed`, para que el panel distinga «se agotó el saldo de la cuenta del proveedor» de un fallo genérico; el paquete `sam` traduce `errors.credits` y la vista cae en el mensaje genérico cuando la clave no existe.
- **Sam: conversaciones múltiples por perfil (#88).** `SamRuntime` deja de tener un hilo único por actor: `conversations()`, `startConversation()`, `findForActor()`, `messages(User, ?AiConversation)` y `send()` con conversación seleccionada; `deleteConversation()` y `forgetAll()` borran hilos del actor y auditan solo conteos en `mcp_tool_invocations` (`sam_conversations`, `action: delete_conversation|forget_all`), sin guardar nunca el contenido. `ai_messages` gana `context` con el `SamContext` saneado del turno y el título se deriva del primer mensaje de forma determinista (`SamTitle`, `SAM_TITLE_CHARS`, sin modelo). `sam:prune-conversations` purga además conversaciones vacías más antiguas que la retención. La conversación única existente se conserva con su historial.
- **Sam: runtime y contrato de agente de plataforma (#84).** `App\Ai\Sam\*`: `SamRegistry` (contributors, perfil y superficie), `SamContributor`, `SamProfile`, DTOs (`SamContext`, `SamProposal`, `SamProposalResult`, `SamProposalPresenter`), `SamRuntime`, `SamInstructions`, `SamAgent`, `SamTurn` y `SamRedactor`. El runtime compone instrucciones, resuelve solo lecturas y propuestas (bases `SamReadTool`/`SamProposeTool`), aplica la cuota `sam.turn`, persiste `ai_conversations.profile = sam` y `ai_messages.correlation_id`, audita en `mcp_tool_invocations` con correlación, redacta la frase `CONFIRM …` y delega la confirmación en el módulo. Incluye el hook de superficie en el layout (`SAM_ENABLED`, permiso y componente registrado), `config/lnx_sam.php`, `.env.example` y el comando `sam:prune-conversations`. La identidad y el panel quedan en el paquete `luinuxscl/sam` (#85).

### Cambiado

- **El dashboard pasa a un host de widgets.** Los paneles de admin y root se declaran como widgets (`lnx.users`, `lnx.root_tools`) y la cuenta como `lnx.account`; la vista `resources/views/livewire/dashboard.blade.php` ya no contiene cifras ni tarjetas cableadas. La suite del kit cubre registry, host y permisos.
- **`/chat` deja de ser una superficie de usuario (#85).** La ruta sale de la navegación y redirige a `dashboard?sam=open`, donde el panel global del paquete `luinuxscl/sam` se abre si `SAM_ENABLED` y el actor tiene `sam.use`. Se retiran la tarjeta del dashboard y la entrada del `NavigationSeeder`; en instalaciones existentes, `navigation:sync` oculta el ítem huérfano. El componente, el servicio y las conversaciones heredadas se conservan hasta la limpieza mayor. Este cambio es incompatible: la próxima release del kit debe ser MAJOR.

### Corregido

- **Estado vacío de los widgets y atajos de Sam.** `x-lnx-ui::widget` (lnx-ui v1.27.1) ignora los comentarios que Livewire inyecta alrededor de cada directiva antes de decidir si el slot está vacío, así que los widgets muestran su mensaje de vacío en vez de una tarjeta en blanco. Un widget `Action` cuyos elementos traen `prompt` se pinta como tarjeta con botones apilados a ancho completo: los atajos del agente dejan de truncarse en una columna estrecha.
- **Endurecimiento del runtime de Sam.** Las bases `SamReadTool`/`SamProposeTool` re-comprueban `SAM_ENABLED` (denegación auditada como `disabled`), el runtime rechaza nombres de herramienta duplicados entre módulos (`duplicate_name`) y la redacción de `CONFIRM …` exige un token de 6 o más caracteres para evitar falsos positivos.

## [1.0.0] - 2026-11-01

Primera release bajo el esquema SemVer del kit completo.

### Añadido

- **Esquema de versionado `vX.Y.Z`:** `lnx_version` en `.ai/kit-manifest.json`, etiquetas anotadas sobre `main` y reglas MAJOR/MINOR/PATCH documentadas en el README.
- **Comprobación de versión en proyectos derivados:** `python3 scripts/update-agent-kit.py --source-kit <lnx> --check-version` compara el `lnx_version` del proyecto con el del clon y devuelve código 1 si está desactualizado o sin versión registrada.

### Cambiado

- **Vista inicial (landing):** pasa a una composición de una sola pantalla, sin header, selector de idioma ni control de tema; incluye halo del color de acento, franja compacta del stack y footer del kit.
- **Apariencia por defecto:** la primera visita arranca en modo claro (`Appearance::defaultColorScheme()` y semilla en `partials/head.blade.php`), sin romper la opción **System** de los ajustes de cuenta.
- **Sin `class="dark"` fijo** en `welcome` ni en los layouts: el modo guardado se aplica desde el `<head>` antes de pintar.
