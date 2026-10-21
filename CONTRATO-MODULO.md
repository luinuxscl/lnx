# Contrato de módulo

> **Estado:** implementado (9 de 9 puntos) · **Etapa 10** ([#39](https://github.com/luinuxscl/lnx/issues/39)), **Etapa 11** ([#53](https://github.com/luinuxscl/lnx/issues/53)) y **Centro de ayuda** ([#78](https://github.com/luinuxscl/lnx/issues/78)) · **Última revisión:** 2026-10-20
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
| Identidad por defecto | `App\Support\Settings\IdentitySeederRegistry` en el `boot()` del provider, con prioridad sobre el kit | ✅ |
| Skills para agentes | `resources/boost/skills/<skill>/SKILL.md`; Boost las distribuye | ✅ |

## 3. El contrato (puntos y estado)

| # | Punto | Estado |
| --- | --- | --- |
| 1 | **Permisos propios**: el paquete registra y siembra sus permisos (idempotente); `admin` los recibe por defecto | ✅ Implementado (§3.1) |
| 2 | **Acceso por registro**: relacionar un usuario con los registros que puede ver | ✅ Implementado (§3.6) |
| 3 | **Aplicaciones de IA**: el paquete declara sus aplicaciones, con cuota y consumo propios | ✅ Implementado (§3.2) |
| 4 | **Notificaciones propias**: tipos del módulo dentro del centro existente | ✅ Implementado (§3.4) |
| 5 | **Página de paquete genérica**: generalizar `LnxUiPackage` + `/admin/packages` a un registro de paquetes | ✅ Implementado (§3.5) |
| 6 | **Escritura por MCP declarable** | ✅ Implementado (§4) |
| 7 | **Skills del módulo vía Boost** | ✅ Documentado (§3.3) |
| 8 | **Identidad por defecto**: el paquete aporta sus defaults de marca con prioridad sobre el kit | ✅ Implementado (§3.7) |
| 9 | **Documentación y FAQ**: el paquete publica guías y preguntas frecuentes en el Centro de ayuda, filtradas por rol | ✅ Implementado (§3.8) |

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

### 3.3 Skills del módulo vía Boost (documentado)

Un paquete entrega sus skills a los agentes poniéndolas en `resources/boost/skills/<skill>/SKILL.md`, con el frontmatter estándar (`name` en kebab-case y `description`). Boost las descubre y las distribuye; no hay que registrarlas a mano en la app.

- **Descubrimiento**: Boost mira las dependencias **directas** del proyecto (`composer require <owner>/<paquete>`) y lee su carpeta `resources/boost/skills`. Las dependencias transitivas se excluyen: un paquete no puede inyectar skills por venir arrastrado.
- **Distribución**: `php artisan boost:update` copia cada skill del paquete a `.ai/skills/<skill>/` (ruta canónica) y la enlaza a los agentes configurados en `boost.json`. `php artisan boost:list-skills` lista las skills disponibles mostrando el paquete de origen.
- **Precedencia**: una skill **local** en `.ai/skills/` con el mismo nombre gana sobre la del paquete. lnx mantiene copias versionadas y sincronizadas de las skills load-bearing de sus paquetes —por ejemplo `lnx-ui-release`, cuya fuente canónica es la skill de Boost del paquete `lnx-ui`— con una nota de fuente canónica en la cabecera.
- **Ejemplos verificables** en este checkout: `luinuxscl/lnx-ui` → `lnx-ui-release`, `spatie/laravel-permission` → `laravel-permission-development`, `livewire/blaze` → `blaze-optimize`.

### 3.4 Notificaciones propias del módulo (implementado)

Un paquete declara sus tipos de notificación en el `boot()` de su provider; el centro de notificaciones existente los muestra, enlaza y marca como leídos sin tocar el kit.

```php
use App\Support\Notifications\NotificationTypeRegistry;

public function boot(): void
{
    app(NotificationTypeRegistry::class)->register('luz', [
        LuzDocumentReady::class => [
            'message'    => fn (DatabaseNotification $n): string => __('Document :name is ready.', ['name' => $n->data['name']]),
            'url'        => fn (DatabaseNotification $n): string => route('luz.documents.show', $n->data['document_id']),
            'permission' => 'luz.view_document',
        ],
    ]);
}
```

- El tipo se registra por la **clase** de la notificación que el paquete envía (cualquier subclase de `Illuminate\Notifications\Notification`); nada se renderiza por estar el paquete en `vendor/`.
- `message` (obligatorio) construye el texto a partir de los datos; `url` (opcional) es el destino del enlace; `permission` (opcional) exige que la identidad pueda ver el enlace.
- Un tipo **no declarado** conserva el respaldo genérico y su contenido guardado nunca se renderiza: declarar es lo que habilita mostrar y navegar.
- Marcar como leída funciona de forma genérica por identificador, sin código del paquete.

### 3.5 Página de paquete genérica (implementado)

Un paquete declara su identidad en el `boot()` de su provider; el kit lo lista en la página genérica de paquetes (`/admin/packages`, solo `root`) con su versión instalada, la última disponible y el comando de actualización. `lnx-ui` no forma parte de este registro: al ser el paquete de componentes del propio kit —con su galería de diseño— conserva su página dedicada (`/admin/packages/lnx-ui`), servida por el mismo resolvedor de versiones.

```php
use App\Support\Packages\PackageRegistry;

public function boot(): void
{
    app(PackageRegistry::class)->register('luz', [
        'name'        => 'luinuxscl/luz', // obligatorio, `vendor/package`
        'label'       => 'Luz',           // obligatorio, texto o clave de traducción
        'description' => 'Historial médico familiar.', // opcional
        'repository'  => 'luinuxscl/luz', // opcional; por defecto, el nombre
        'url'         => 'https://…',     // opcional, enlace externo (http/s)
        'update_command' => 'composer update luinuxscl/luz', // opcional; por defecto se deriva
    ]);
}
```

- **Declaración explícita.** Nada aparece por estar el paquete en `vendor/`: declara el propio provider. Registrar dos veces el mismo nombre es idempotente (gana la última declaración).
- **Datos que muestra.** La página lee la versión instalada de Composer y consulta la última etiqueta de versión en la API de GitHub; si la última es mayor, marca **Actualización disponible** y ofrece el comando para copiar. Sin token de GitHub solo muestra la versión instalada (el estado queda `missing_token`).
- **Validación.** El nombre y el repositorio deben tener la forma `vendor/package`, la etiqueta no puede estar vacía y una `url` debe ser `http(s)`. Un descuido es un error detectable (`InvalidArgumentException`), no una fila rota.
- **Resolvedor compartido.** Tanto el registro como la página de `lnx-ui` usan `App\Support\Packages\PackageVersion`, que resuelve la versión instalada (`Composer\InstalledVersions`) y la última disponible por nombre de paquete, con caché por paquete. El token y el TTL viven en `config/packages.php` (`PACKAGES_GITHUB_TOKEN`/`GITHUB_TOKEN`, `PACKAGES_UPDATE_CHECK_TTL`).
- **Alcance.** Registrar aquí es solo para que un módulo sea visible en la superficie de paquetes del admin; no concede permisos, cuotas ni escritura. Esos se declaran en sus propios registros (§3.1, §3.2, §4).

### 3.6 Acceso por registro (implementado)

Un paquete **relaciona a un usuario con los registros que puede ver** y el kit le da un mecanismo uniforme para guardar esa relación, **filtrar las consultas por ella** y hacerla valer en las superficies compartidas (MCP y admin). El dominio decide **quién ve qué**; el kit solo estandariza **la forma**.

**Contrato (lo que el kit garantiza)**

1. **Modelo accesible.** Un modelo que se filtra por identidad implementa `App\Support\RecordAccess\AccessControlled` y expone `scopeAccessibleTo(Builder $query, User $user): Builder`. El filtro se aplica **en la consulta** (authorization-first), nunca en memoria: las superficies comparten `Modelo::accessibleTo($user)`. Un registro hijo hereda el acceso de su padre uniendo contra el scope del padre.
2. **Registro explícito.** El provider declara sus modelos accesibles con `App\Support\RecordAccess\RecordAccessRegistry`; registrar una clase que no sea un modelo Eloquent que implemente `AccessControlled` es un error detectable (`InvalidArgumentException`):
   ```php
   use App\Support\RecordAccess\RecordAccessRegistry;

   public function boot(): void
   {
       app(RecordAccessRegistry::class)->register('luz', [Patient::class]);
   }
   ```
3. **Autorización por registro.** Las policies del paquete comprueban la pertenencia del usuario al registro (ver, editar). Las superficies compartidas (**MCP, admin**) filtran siempre con `accessibleTo($user)` y revalidan el permiso por operación; ninguna devuelve registros sin pasar por el scope.
4. **Pertenencia genérica (opcional).** El kit ofrece la tabla polimórfica `record_access`, el modelo `App\Models\RecordAccess` y el trait `App\Support\RecordAccess\GrantsRecordAccess` para relacionar usuarios con **cualquier** registro. La relación es `accessGrants()`, con dos ayudantes: `grantAccessTo(User $user, RecordAccessAbility $ability = View, ?CarbonInterface $expiresAt = null)` (fusiona abilities y no duplica la fila) y `hasRecordAccess(User $user, RecordAccessAbility $ability = View)`. Es una comodidad, no una obligación: un paquete puede definir su propia relación si necesita algo más rico y aun así cumplir el contrato.

**Ejemplo (Luz, multi-paciente)**

```php
class Patient extends Model implements AccessControlled
{
    use GrantsRecordAccess;

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(Role::Root)) {
            return $query; // root omite el filtro; admin NO lo omite por defecto
        }

        return $query->where(fn (Builder $q) => $q
            ->where('owner_id', $user->id)
            ->orWhereHas('accessGrants', fn (Builder $g) => $g
                ->where('user_id', $user->id)
                ->where(fn (Builder $e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now()))));
    }
}
```

- **Hijos** (documentos, prescripciones, exámenes, horas): su `accessibleTo` une contra `patients` con el scope del padre, de modo que heredan el acceso sin duplicar la lista.
- **MVP:** Luis es `root` y ve todos los pacientes; la relación ya existe y no limita. **Fase 2:** la familia recibe `grants` por paciente y los médicos accesos temporales (`expires_at`), sin rediseño.

**Decisiones adoptadas (2026-10-11)**

1. **Alcance de `admin`:** sin bypass; solo `root` omite el filtro (consistente con las policies).
2. **Granularidad de `abilities`:** `view`/`manage` (`App\Support\RecordAccess\RecordAccessAbility`); un grant sin abilities almacenadas cubre ambas.
3. **Accesos temporales:** en el grant (`expires_at`); un grant vencido se ignora tanto en el scope como en `hasRecordAccess`. Los enlaces públicos con token son otra pieza (Fase 2).
4. **Herencia:** convención documentada (unir contra el scope del padre), sin helper del kit.
5. **Verificación:** pruebas Pest del contrato (`tests/Feature/RecordAccessTest.php`).

**Qué NO hace el kit**

- No define la relación de dominio ni el significado clínico; no concede acceso por instalación.
- No sustituye las policies: **complementa** el filtrado en consulta con la comprobación por operación.
- No permite filtrar en memoria ni devolver colecciones sin aplicar `accessibleTo`.

### 3.7 Identidad por defecto (implementado)

Un paquete aporta los **defaults de identidad de su marca** (nombre, lema, footer, meta descripción, logos/iconos, idioma y zona horaria) declarando su propio seeder en el `boot()` de su provider. El kit los ejecuta al sembrar **en orden de prioridad** (`paquete > lnx`) y **solo rellena lo que aún no está definido**, por lo que **la configuración de root siempre gana** y nada se pisa.

```php
use App\Support\Settings\IdentitySeederRegistry;

public function boot(): void
{
    app(IdentitySeederRegistry::class)->register('luz', LuzIdentitySeeder::class, priority: 100);
}
```

`LuzIdentitySeeder` es un `Illuminate\Database\Seeder` cuyo `run()` aplica sus valores con `App\Actions\ApplyIdentityDefaults`:

```php
use App\Actions\ApplyIdentityDefaults;
use Illuminate\Database\Seeder;

class LuzIdentitySeeder extends Seeder
{
    public function run(): void
    {
        app(ApplyIdentityDefaults::class)->handle([
            'site_title' => 'Luz',
            'site_tagline' => 'Historial médico familiar',
            'footer_text' => 'Hecho por Luz',
            'locale' => 'es',
            'timezone' => 'America/Santiago',
            // 'logo_path' => 'branding/luz-logo.png', // el paquete publica el asset en el disco `public`
        ]);
    }
}
```

- **Registro validado.** `register(source, seeder, priority)` exige un `source` no vacío y un `seeder` que extienda `Seeder`; cualquier otra cosa lanza `InvalidArgumentException`. Re-registrar un `source` reemplaza su seeder (idempotente).
- **Precedencia.** Los seeders se ejecutan por `priority` descendente (empate: orden de registro). El kit registra su seeder base con la prioridad más baja (`IdentitySeederRegistry::KIT_PRIORITY`), así que a igualdad de campo gana el del paquete. Un valor ya guardado en la fila `settings` (root) **nunca** se sobrescribe.
- **Campos declarables.** `site_title`, `site_tagline`, `footer_text`, `meta_description`, `logo_path`, `logo_dark_path`, `favicon_path`, `apple_touch_icon_path`, `locale` y `timezone`. Un campo desconocido es un error detectable; los valores vacíos o `null` se ignoran.
- **Sin fila por defecto.** Un valor igual al fallback efectivo del kit (config o los `Setting::DEFAULT_*`) no se persiste, de modo que **sin paquete el resultado es idéntico al actual** (no se crea fila `settings`). Con paquete, se persisten solo los campos que este aporta.
- **Idempotencia.** Ejecutarlo dos veces no cambia nada ni duplica valores.
- **Ejecución.** `SeedIdentity` corre desde `DatabaseSeeder` (`migrate --seed`, `db:seed`) y desde `php artisan lnx:reset`, tras roles y navegación y antes de los datos demo.
- **Assets.** El paquete es responsable de publicar sus logos/iconos en el disco `public` y pasar la ruta relativa; `Setting::logoUrl()` y compañía ya la resuelven.
- **Idioma y zona horaria.** Se guardan en columnas propias de `settings` (`locale`, `timezone`), editables por root en **Configuración general**, y `ApplySiteSettings` las aplica a la request (el idioma del sitio es el fallback de `SetLocale`).

### 3.8 Documentación y FAQ del módulo (implementado)

Un paquete publica sus **guías y preguntas frecuentes** en el Centro de ayuda (`/help`) registrándolas en el `boot()` de su provider. El kit no muestra contenido por el simple hecho de que el paquete esté en `vendor/`: solo aparece lo declarado y solo para las identidades autorizadas.

```php
use App\Support\Help\HelpRegistry;

public function boot(): void
{
    app(HelpRegistry::class)->register('luz', [
        'luz.primeros-pasos' => [
            'type' => 'article',                         // article | faq
            'group' => 'luz::help.groups.getting_started',
            'title' => 'luz::help.getting_started.title',
            'summary' => 'luz::help.getting_started.summary',
            'body' => 'luz::help.getting_started.body',   // Markdown traducido
            'roles' => ['admin'],                         // vacío = toda identidad autenticada
            'order' => 1,
            'route' => 'luz.dashboard',                   // opcional: enlace «Ir a la función»
        ],
        'luz.faq-accesos' => [
            'type' => 'faq',
            'group' => 'luz::help.groups.faq',
            'title' => 'luz::help.faq.access.title',
            'summary' => 'luz::help.faq.access.summary',
            'body' => 'luz::help.faq.access.body',
            'order' => 2,
        ],
    ]);
}
```

- **Slug obligatorio y propio.** Cada entrada se indexa por un slug con el prefijo del paquete (`luz.`). Un slug vacío, sin prefijo o duplicado lanza `InvalidArgumentException`.
- **Campos obligatorios.** `type` (`article` o `faq`), `group`, `title`, `summary` y `body` son claves de traducción no vacías; `roles` es un array de valores de `App\Enums\Role`, `order` un entero y `route` una ruta nombrada existente o `null`.
- **Visibilidad.** `root` ve todo lo declarado. El resto ve las entradas sin `roles` y las que coinciden con alguno de sus roles. Nada restringido aparece en el listado, la búsqueda, un enlace profundo ni en `ProductHelpSearchTool`.
- **Prioridad de la customización.** Toda entrada de un `source` distinto de `lnx` se muestra **siempre por encima** del contenido del starterkit, con independencia de su `order`. Dentro de cada source se respeta `order` y, en empate, el slug. La regla aplica al listado, la búsqueda y `ProductHelpSearchTool`.
- **Contenido localizado.** El cuerpo se resuelve al idioma actual y se renderiza con `Str::markdown(..., ['html_input' => 'strip', 'allow_unsafe_links' => false])`; nunca se ejecuta HTML crudo ni se permiten enlaces inseguros.
- **Persistencia y precedencia de root.** `php artisan help:sync` materializa los grupos y entradas declaradas en `help_groups` y `help_entries`; se ejecuta también al sembrar. Es idempotente y clasifica cada entrada en **auto** (intacta del paquete), **editado por root** (`overridden`: textos es/en, roles, orden o visibilidad propios) o **disponible** (declarada pero aún sin fila, por ejemplo antes de sincronizar tras instalar el paquete). La sincronización actualiza las claves de traducción del paquete sin pisar un override, y oculta —nunca borra— las entradas cuya declaración desaparece. El editor `/admin/help` (solo `root`) permite editar, reordenar, ocultar/publicar y restaurar al valor del paquete, con cada cambio registrado en Actividad administrativa. `HelpCatalog` es el lector que combina declaración y persistencia.
- **Contenido del kit.** lnx declara sus propias guías desde `App\Providers\HelpServiceProvider` con Markdown bilingüe en `lang/es/help.php` y `lang/en/help.php`.
- **Ayuda contextual.** El paquete usa `<x-help-topic slug="luz.primeros-pasos" />` en sus pantallas para enlazar al tema; si el slug no existe o el usuario no puede verlo, el componente no renderiza nada. En el registro de tipos de notificación (`App\Support\Notifications\NotificationTypeRegistry`) la clave opcional `help` declara un callable que devuelve el slug del tema; el centro de notificaciones solo muestra el enlace si la entrada es visible para la identidad.
- **Búsqueda semántica.** El paquete **no declara nada adicional**: sus entradas publicadas se indexan automáticamente en la base de sistema `product_help`, un documento por entrada e idioma, al ejecutar `help:sync` o al guardar desde `/admin/help`. La búsqueda respeta el idioma actual y vuelve a comprobar los roles de la entrada antes de devolverla; el chat cita el título y enlaza a `/help?topic=<slug>`. La indexación usa `IndexKnowledgeDocumentJob`, por lo que requiere un worker de cola.

## 4. Escritura por MCP (implementado)

### 4.1 Principios

El servidor MCP de lnx es **de solo lectura por defecto**. Un módulo puede declarar operaciones de escritura, pero **la escritura es una capacidad explícita, acotada y auditable**, nunca un efecto colateral de estar instalado.

1. **Apagado por defecto.** `MCP_SERVER_ALLOW_WRITES=false`. Mientras esté apagado, ninguna herramienta de escritura se registra ni se anuncia.
2. **Habilitación del operador.** Encender la escritura es una decisión de despliegue, no del paquete.
3. **Declaración explícita por paquete.** El paquete registra cada herramienta de escritura; nada se expone por estar en `vendor/`.
4. **Permisos por operación.** Un token de lectura no puede invocar una herramienta de escritura, aunque la conozca (depende del punto 1 del contrato).
5. **Seguro por diseño, no por confianza.** Las reglas protegen aunque el agente se equivoque o alucine.

### 4.2 Cómo declara un paquete una escritura

```php
use App\Mcp\Attributes\WritesData;
use App\Mcp\Tools\WriteTool;

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
- **Registro**: el provider del paquete registra sus herramientas en `App\Mcp\McpToolRegistry` (`app(McpToolRegistry::class)->register('luz', [UploadDocumentTool::class])`); `LnxServer` las fusiona en `createContext()` y ya no mantiene una lista fija de ellas.
- **Clasificación obligatoria**: una herramienta que escribe **debe** declarar `#[WritesData]`. No declararlo es un error detectable (`InvalidArgumentException` al ejecutar y en el comando `mcp:verify-writes`), no un descuido silencioso.

### 4.3 Requisitos que el kit provee (no cada módulo)

| Requisito | Qué significa |
| --- | --- |
| **Idempotencia** | Toda escritura acepta una clave de idempotencia; repetir la llamada no duplica datos. Reintentar es seguro. |
| **Auditoría con diff** | Se registra campo, valor anterior, valor nuevo y actor, además del contexto estructural que ya se guarda. |
| **Permisos por operación** | Cada herramienta declara el permiso que exige; el descubrimiento solo la muestra a quien puede invocarla. |
| **Límite de frecuencia** | Reutiliza el límite por token existente, con un valor propio para escrituras. |
| **Revocación individual** | Los tokens son por agente y revocables, como los actuales. |

**Nota sobre el diff y datos sensibles.** En un dominio clínico el diff contiene valores de salud. Se guardan en la misma base que el dato (no salen del servidor), con la retención que defina el operador (`MCP_SERVER_AUDIT_RETENTION_DAYS`, purgado por `mcp:prune-invocations`), y **nunca** en logs de aplicación. El detalle puede apagarse con `MCP_SERVER_AUDIT_DIFF=false`.

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

Cada punto del contrato se valida con **Luz**, el primer módulo. Un punto que Luz no use no entra. Al cerrar la etapa: `CONTRATO-MODULO.md`, el ROADMAP y el CHANGELOG del kit quedan actualizados, y [`references/packages.md`](.ai/skills/implement-feature/references/packages.md) de la skill del kit refleja el contrato vigente. La referencia ya existe y resume las capacidades declarables; se actualiza con cada punto que se cierre.

## 6. Versionado

Este documento describe el contrato **vigente** del kit. Los cambios de comportamiento (no las correcciones de redacción) se registran en `.ai/CHANGELOG.md` y, si afectan a los módulos instalados, en la guía de actualización del kit.
