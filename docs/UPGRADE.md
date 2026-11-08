# Actualizar un proyecto derivado

Cada release del kit puede traer cambios que un proyecto derivado **sí** debe
aplicar a mano. Este archivo reúne esas migraciones, una por release, con el
orden exacto y la verificación que las cierra. Las releases sin entrada aquí se
adoptan actualizando dependencias y `lnx_version` en `.ai/kit-manifest.json`
(ver [«Versionado de lnx»](../README.md#versionado-de-lnx-semver)).

> El kit para agentes (`.ai/`) se sincroniza con
> `scripts/update-agent-kit.py`; el código de la aplicación, no. Si una entrada
> de este archivo toca `app/`, `config/` o `composer.json`, es trabajo manual.

## Runtime de Sam empaquetado (próxima release mayor)

El runtime de Sam dejó de vivir en la aplicación. Ahora es el paquete público
**MIT `luinuxscl/lnx-sam`** (namespace `Luinuxscl\LnxSam`): el registro de
contribuciones, el manifiesto de capacidades, las bases de herramientas, el
modo efectivo, las conversaciones y los comandos de introspección. El kit ya no
lo publica y el andamio de nuevos proyectos lo instala como dependencia.

Motivo: [`docs/adr/0002-capacidades-y-empaquetado-del-runtime.md`](adr/0002-capacidades-y-empaquetado-del-runtime.md).
Contrato vigente: [`CONTRATO-MODULO.md`](../CONTRATO-MODULO.md) §3.9.

### 1. Requiere el paquete

```bash
composer config repositories.lnx-sam vcs https://github.com/luinuxscl/lnx-sam.git
composer require "luinuxscl/lnx-sam:^0.2"
```

El paquete es público, así que basta el repositorio VCS: no necesita token ni
credencial de GitHub. `LnxSamServiceProvider` se descubre solo; **no** lo añadas
a `bootstrap/providers.php`.

### 2. Borra la copia del runtime en la app

- El directorio completo `app/Ai/Sam/`.
- Los cuatro comandos `app/Console/Commands/SamCapabilitiesCommand.php`,
  `SamDescribeCommand.php`, `SamRunCommand.php` y `SamDoctorCommand.php`. Los
  registra el paquete bajo el mismo nombre (`sam:capabilities`, `sam:describe`,
  `sam:run`, `sam:doctor`).
- Los `singleton(SamRegistry::class)` y `singleton(SamAccess::class)` del
  proveedor de la aplicación: los declara el paquete.

`sam:prune-conversations` **se queda** en la app: purga sus propias tablas y
solo usa el runtime para leer la retención.

### 3. Reapunta los imports al namespace del paquete

Todos los consumidores —contribuidores, herramientas, componentes Livewire,
pruebas y comandos propios— pasan de `App\Ai\Sam\*` a `Luinuxscl\LnxSam\*`.

El paquete publica alias de compatibilidad para `App\Ai\Sam\*` y para los
cuatro comandos, pero **son un puente, no una solución**:

- no cubren **declaraciones de tipo**: PHP no autoloada la clase del parámetro
  al comprobar el tipo, así que un `SamContext $context` con el nombre viejo
  falla;
- no cubren **claves del contenedor**, que son cadenas:
  `app()->instance('App\Ai\Sam\SamInstructions', …)` no afecta a la clase real.

Sustituye el texto, no confíes en el alias. Comprobación rápida:

```bash
grep -rn "App\\\\Ai\\\\Sam" app/ tests/ config/ database/ --include='*.php' | grep -v vendor
```

Debe quedar vacío. Los alias se retiran en la próxima versión mayor.

### 4. Conserva lo que es de la app

- `config/lnx_sam.php`: es tuyo. El paquete trae sus valores por defecto, pero
  una copia publicada en la app gana para las claves que ya existían; revisa las
  nuevas comparando con `vendor/luinuxscl/lnx-sam/config/lnx_sam.php`.
- Las migraciones `ai_conversations.profile`, `ai_messages.correlation_id` y
  `ai_messages.context`.
- `.env`: mismas variables (`SAM_ENABLED`, `SAM_PERMISSION`, `SAM_MAX_MODE`,
  `SAM_MAX_CONTEXT_MESSAGES`, `SAM_RETENTION_DAYS`…). La tabla de claves vive en
  [`CONTRATO-MODULO.md`](../CONTRATO-MODULO.md) §3.9 y en el README del paquete.

### 5. Adopta el rechazo auditable (runtime `^0.2`)

Una herramienta de módulo que se niega a leer o a proponer ya no devuelve un
texto suelto: lanza `Luinuxscl\LnxSam\Tools\SamToolDenied` con el motivo
(`forbidden`, `out_of_scope`, `validation`, `failed`) y el mensaje que debe leer
el modelo. El runtime audita `denied` con esa razón.

Si tu módulo devolvía una cadena para negarse (`return 'not allowed'`), la
invocación quedaba auditada como éxito sin payload: un resultado falso en la
actividad que ve la persona usuaria. Migra esos retornos al lanzamiento, o
traduce tu excepción de dominio con `SamToolDenied::forbidden()` y compañía.

### 6. Verifica

```bash
php artisan sam:doctor --actor=<email>       # sin drift: capacidades ↔ herramientas
php artisan sam:run <clave> --actor=<email> --dry-run   # previsualiza sin escribir
php artisan test
vendor/bin/pint --dirty --format agent
```

En CI, `sam:doctor` con código de salida distinto de cero es drift: una
capacidad sin herramienta, una herramienta sin capacidad o un `WritesData` que
contradice el manifiesto.

### 7. Deuda que esta migración deja anotada

- Los alias `App\Ai\Sam\*` y los cuatro comandos existen solo como puente y se
  retiran en la siguiente versión mayor.
- El paquete no trae identidad ni interfaz: eso sigue en `luinuxscl/sam` y en
  `luinuxscl/lnx-ui`.
