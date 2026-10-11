# Contrato de módulo

> **Estado:** en diseño · **Etapa 10** ([#39](https://github.com/luinuxscl/lnx/issues/39)) · **Última revisión:** 2026-10-10
> Define **qué puede declarar un módulo (paquete Composer) en lnx** y **bajo qué reglas**. El contrato se deriva del módulo real que lo valida: **Luz** (`luinuxscl/luz`). Nada entra aquí que Luz no use.

---

## 1. Qué es un módulo

Un **módulo** es un paquete Composer que aporta funcionalidad a cualquier app derivada de lnx. El provider se autodescubre con `extra.laravel.providers`; la app no lo registra a mano.

- **La funcionalidad vive en el módulo**, no en el kit.
- La app es una **cáscara**: identidad, `.env`, dominio y `composer require <owner>/<modulo>`.
- Regla de diseño antes de agregar algo al kit: *¿esto sirve a toda app derivada?* Si no, va al módulo.

## 2. Lo que un paquete ya puede declarar hoy

| Capacidad | Cómo | Estado |
| --- | --- | --- |
| Rutas, migraciones, traducciones, comandos, políticas, Livewire | Provider del paquete (estándar Laravel) | ✅ |
| Vistas sobrescribibles por la app | `loadViewsFrom` + `resources/views/vendor/<paquete>` | ✅ |
| Clases Tailwind dentro de `vendor/` | `@source '../../vendor/<owner>/<paquete>/resources/views'` en `resources/css/app.css` | ✅ |
| Menú del sidebar | Macro `->menu([...])` + `php artisan navigation:sync` (autopublicación y precedencia de root) | ✅ |
| Skills para agentes | `resources/boost/skills/<skill>/SKILL.md`; Boost las distribuye | ✅ |

## 3. El contrato (puntos y estado)

| # | Punto | Estado |
| --- | --- | --- |
| 1 | **Permisos propios**: el paquete registra y siembra sus permisos (idempotente); `admin` los recibe por defecto | ✅ Implementado (§3.1) |
| 2 | **Acceso por registro**: relacionar un usuario con los registros que puede ver | En diseño |
| 3 | **Aplicaciones de IA**: el paquete declara sus aplicaciones, con cuota y consumo propios | ✅ Implementado (§3.2) |
| 4 | **Notificaciones propias**: tipos del módulo dentro del centro existente | En diseño |
| 5 | **Página de paquete genérica**: generalizar `LnxUiPackage` + `/admin/packages` a un registro de paquetes | En diseño |
| 6 | **Escritura por MCP declarable** | **Estándar definido (§4)** |
| 7 | **Skills del módulo vía Boost** | ✅ ya soportado; falta documentarlo |

### 3.1 Permisos propios (implementado)

Un paquete registra sus permisos granulares en el `boot()` de su provider; el kit los siembra de forma idempotente y `admin` los recibe por defecto.

```php
use App\Support\Permissions\PermissionRegistry;

public function boot(): void
{
    app(PermissionRegistry::class)->register('luz', [
        'luz.view_patient',
        'luz.manage_patient',
    ]);
}
```

- Los nombres **deben** llevar el prefijo del paquete (`luz.`); registrar un nombre sin prefijo (o vacío) lanza `InvalidArgumentException`, para que un descuido sea un error detectable y no un permiso huérfano.
- El comando `permissions:sync` crea cada permiso declarado con `findOrCreate` y lo concede al rol `admin`. `root` los omite por `Gate::before`.
- `RolePermissionSeeder` ejecuta el mismo sync, de modo que `php artisan migrate --seed` deja los permisos listos en una instalación nueva.
- Reejecutar la sincronización no duplica permisos ni concesiones.
- Nada se concede por estar el paquete en `vendor/`: la declaración es explícita. Un token o usuario sin la concesión no pasa `$user->can('luz.view_patient')`.

### 3.2 Aplicaciones de IA del módulo (implementado)

Un paquete declara sus aplicaciones de IA, cada una con su cuota, en el `boot()` de su provider. La app las usa con el `AiGateway` y el consumo se registra y se limita bajo esa aplicación, sin editar `config/lnx_ai.php`.

```php
use App\Ai\AiApplicationRegistry;

public function boot(): void
{
    app(AiApplicationRegistry::class)->register('luz', [
        'luz.extraction' => ['window' => 'hour', 'requests' => 20, 'tokens' => 100000],
        'luz.search'     => ['window' => 'hour', 'requests' => 60, 'tokens' => 150000],
        'luz.analysis'   => ['window' => 'day',  'requests' => 50, 'tokens' => 200000],
    ]);
}
```

- Los nombres **deben** llevar el prefijo del paquete (`luz.`); registrar uno sin prefijo (o vacío) es un error detectable (`InvalidArgumentException`).
- La cuota declarada se aplica de forma independiente a la cuota por usuario: consumir con `application: 'luz.extraction'` descuenta del contador y del registro de esa aplicación, y agota su límite antes que el global.
- Un despliegue puede **sobrescribir** la cuota declarada desde `config/lnx_ai.php` (`limits.applications`); la configuración tiene precedencia sobre el paquete.
- Si el paquete declara la aplicación sin cuota, se usa la cuota `default` del despliegue (nunca queda sin límite por omisión).
- Las aplicaciones declaradas aparecen junto a las del kit en el panel de uso/estado de IA (`/admin/ai`), para que su cuota sea visible y auditable.

## 4. Estándar de escritura por MCP

### 4.1 Principios

El servidor MCP de lnx es **de solo lectura por defecto**. Un módulo puede declarar operaciones de escritura, pero **la escritura es una capacidad explícita, acotada y auditable**, nunca un efecto colateral de estar instalado.

1. **Apagado por defecto.** `MCP_SERVER_ALLOW_WRITES=false`. Mientras esté apagado, ninguna herramienta de escritura se registra ni se anuncia.
2. **Habilitación del operador.** Encender la escritura es una decisión de despliegue, no del paquete.
3. **Declaración explícita por paquete.** El paquete registra cada herramienta de escritura; nada se expone por estar en `vendor/`.
4. **Permisos por operación.** Un token de lectura no puede invocar una herramienta de escritura, aunque la conozca (depende del punto 1 del contrato).
5. **Seguro por diseño, no por confianza.** Las reglas protegen aunque el agente se equivoque o alucine.

### 4.2 Cómo declararía un paquete una escritura (API propuesta)

```php
use Lnx\Mcp\Attributes\WritesData;
use Lnx\Mcp\Tools\WriteTool;

#[WritesData(
    resource:   'document',
    operation:  'create',
    permission: 'luz.manage_patient',
    idempotent: true,
)]
class UploadDocumentTool extends WriteTool
{
    protected function authorize(User $user): bool
    {
        return $user->can('luz.manage_patient');
    }

    protected function perform(Request $request): Response
    {
        // Delega SIEMPRE en la acción de dominio compartida.
        // Nunca escribe directo contra el modelo.
    }
}
```

- **`WriteTool`** es la base hermana de `ReadOnlyTool` (esa ya hace autorizar → ejecutar → auditar en un `handle()` final). `WriteTool` agrega idempotencia y auditoría con diff.
- **Registro**: el provider del paquete registra sus herramientas en el servidor MCP del kit. `LnxServer` deja de tener la lista fija.
- **Clasificación obligatoria**: una herramienta que escribe **debe** declararlo. No declararlo es un error detectable, no un descuido silencioso.

### 4.3 Requisitos que el kit provee (no cada módulo)

| Requisito | Qué significa |
| --- | --- |
| **Idempotencia** | Toda escritura acepta una clave de idempotencia; repetir la llamada no duplica datos. Reintentar es seguro. |
| **Auditoría con diff** | Se registra campo, valor anterior, valor nuevo y actor, además del contexto estructural que ya se guarda. |
| **Permisos por operación** | Cada herramienta declara el permiso que exige; el descubrimiento solo la muestra a quien puede invocarla. |
| **Límite de frecuencia** | Reutiliza el límite por token existente, con un valor propio para escrituras. |
| **Revocación individual** | Los tokens son por agente y revocables, como los actuales. |

**Nota sobre el diff y datos sensibles.** En un dominio clínico el diff contiene valores de salud. Se guardan en la misma base que el dato (no salen del servidor), con la retención que defina el operador, y **nunca** en logs de aplicación. Definir la retención del diff es una decisión abierta con el operador.

### 4.4 Prohibiciones del estándar

1. **Sin borrado.** No existe operación de borrado por MCP. Se crea, se actualiza y, a lo sumo, se archiva.
2. **Sin sobrescritura de historial.** Un cambio crea un registro nuevo y marca el anterior como reemplazado.
3. **Sin escritura fuera de una acción de dominio.** La lógica no se duplica en la herramienta.
4. **Sin escritura por MCP sin declaración explícita** ni con el interruptor global apagado.
5. **Sin herramientas cuyo efecto dependa de texto libre no validado.**

### 4.5 Configuración

| Variable | Por defecto | Para qué |
| --- | --- | --- |
| `MCP_SERVER_ALLOW_WRITES` | `false` | Habilita el registro de herramientas de escritura |
| `MCP_SERVER_WRITE_RATE_LIMIT` | `30` | Límite por minuto para escrituras |
| `MCP_SERVER_AUDIT_DIFF` | `true` | Guarda el detalle de campos modificados |

### 4.6 Criterios de aceptación

- Con `MCP_SERVER_ALLOW_WRITES=false`, una herramienta de escritura no aparece en el descubrimiento ni responde (`404`/no registrada).
- Un token sin el permiso declarado **no ve** la herramienta y, si la invoca, la invocación queda auditada como `denied`.
- Repetir una escritura con la misma clave de idempotencia **no** crea un segundo registro.
- Toda escritura exitosa deja una entrada de auditoría con el diff (campo, antes, después, actor).
- Una herramienta que escribe sin declararse **falla la verificación** del kit.

## 5. Cómo se valida

Cada punto del contrato se valida con **Luz**, el primer módulo. Un punto que Luz no use no entra. Al cerrar la etapa: `CONTRATO-MODULO.md`, el ROADMAP y el CHANGELOG del kit quedan actualizados, y `references/packages.md` de la skill del kit refleja el contrato vigente.

## 6. Versionado

Este documento describe el contrato **vigente** del kit. Los cambios de comportamiento (no las correcciones de redacción) se registran en `.ai/CHANGELOG.md` y, si afectan a los módulos instalados, en la guía de actualización del kit.
