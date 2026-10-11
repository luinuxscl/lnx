# Paquetes y módulos: qué puede declarar un paquete

Resumen operativo del **contrato de módulo** de lnx para agentes. Un **módulo** es un paquete Composer cuyo provider se autodescubre (`extra.laravel.providers`). Nada se concede ni se expone por estar el paquete en `vendor/`: cada capacidad se **declara explícitamente** en el `boot()` del provider. Fuente canónica: [`CONTRATO-MODULO.md`](../../../../CONTRATO-MODULO.md).

## Capacidades declarables

| Capacidad | Punto de extensión | Declaración del paquete | Contrato |
| --- | --- | --- | --- |
| Rutas, migraciones, vistas, traducciones, comandos, políticas, Livewire | Provider estándar de Laravel | estándar | §2 |
| Menú del sidebar | Macro de ruta `->menu([...])` + `navigation:sync` | etiqueta, icono, grupo, audiencias, roles y `auto` | §2 |
| Permisos propios | `App\Support\Permissions\PermissionRegistry` | `register('luz', ['luz.view_patient', …])`, con prefijo del paquete; `permissions:sync` los materializa y concede a `admin` | §3.1 |
| Aplicaciones de IA | `App\Ai\AiApplicationRegistry` | `register('luz', ['luz.extraction' => ['window' => 'hour', 'requests' => 20, 'tokens' => 100000]])` | §3.2 |
| Skills para agentes | `resources/boost/skills/<skill>/SKILL.md` | Boost las descubre en las dependencias directas y las distribuye a `.ai/skills/` | §3.3 |
| Notificaciones propias | `App\Support\Notifications\NotificationTypeRegistry` | `register('luz', [LuzDocumentReady::class => ['message' => …, 'url' => …, 'permission' => '…']])` | §3.4 |
| Página de paquetes | `App\Support\Packages\PackageRegistry` | `register('luz', ['name' => 'luinuxscl/luz', 'label' => 'Luz', …])`; aparece en `/admin/packages` | §3.5 |
| Escritura por MCP | `App\Mcp\McpToolRegistry` + `#[WritesData]` sobre `WriteTool` | `register('luz', [UploadDocumentTool::class])`; solo se expone con `MCP_SERVER_ALLOW_WRITES=true` y el permiso declarado | §4 |

## Estado del contrato

- **Implementado:** 1 permisos, 3 aplicaciones de IA, 4 notificaciones, 5 página de paquetes, 6 escritura por MCP, 7 skills.
- **En diseño:** 2 acceso por registro (relacionar un usuario con los registros que puede ver).

## Configuración compartida

- `config/packages.php`: `PACKAGES_GITHUB_TOKEN`/`GITHUB_TOKEN` y `PACKAGES_UPDATE_CHECK_TTL` (verificación de versión de la página de paquetes).
- Escritura por MCP: `MCP_SERVER_ALLOW_WRITES`, `MCP_SERVER_WRITE_RATE_LIMIT`, `MCP_SERVER_AUDIT_DIFF`.

## Límites

- No se edita `vendor/`: la funcionalidad y sus declaraciones viven en el paquete.
- Nada se expone por dependencia transitiva: las skills solo se descubren en dependencias directas y el resto exige declaración explícita.
- Registrar una capacidad no sustituye la autorización: cada herramienta o dato revalida permisos y policies vigentes.